<?php
declare(strict_types=1);

namespace Flowpack\SearchPlugin\Controller;

/*
 * This file is part of the Flowpack.SearchPlugin package.
 *
 * (c) Contributors of the Flowpack Team - flowpack.org
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Flowpack\ElasticSearch\ContentRepositoryAdaptor\Eel\ElasticSearchQueryBuilder;
use Flowpack\ElasticSearch\ContentRepositoryAdaptor\ElasticSearchClient;
use Flowpack\ElasticSearch\ContentRepositoryAdaptor\Exception\QueryBuildingException;
use Flowpack\SearchPlugin\Suggestion\SuggestionContextInterface;
use Flowpack\SearchPlugin\Utility\SearchTerm;
use Neos\Cache\Exception as CacheException;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Mvc\View\JsonView;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Neos\Controller\CreateContentContextTrait;

class SuggestController extends ActionController
{
    use CreateContentContextTrait;

    /**
     * @var ElasticSearchClient
     */
    protected $elasticSearchClient;

    /**
     * @var ElasticSearchQueryBuilder
     */
    protected $elasticSearchQueryBuilder;

    /**
     * @Flow\Inject
     * @var VariableFrontend
     */
    protected $elasticSearchQueryTemplateCache;

    /**
     * @Flow\Inject
     * @var SuggestionContextInterface
     */
    protected $suggestionContext;

    /**
     * @var array
     */
    protected $viewFormatToObjectNameMap = [
        'json' => JsonView::class
    ];

    /**
     * @Flow\InjectConfiguration("searchAsYouType")
     * @var array
     */
    protected $searchAsYouTypeSettings = [];

    public function initializeObject(): void
    {
        if ($this->objectManager->isRegistered(ElasticSearchClient::class)) {
            $this->elasticSearchClient = $this->objectManager->get(ElasticSearchClient::class);
            $this->elasticSearchQueryBuilder = $this->objectManager->get(ElasticSearchQueryBuilder::class);
        }
    }

    /**
     * @throws QueryBuildingException|IllegalObjectTypeException|CacheException
     */
    public function indexAction(string $term = '', string $contextNodeIdentifier = '', string $dimensionCombination = null): void
    {
        if ($this->elasticSearchClient === null) {
            throw new \RuntimeException('The SuggestController needs an ElasticSearchClient, it seems you run without the flowpack/elasticsearch-contentrepositoryadaptor package, though.', 1487189823);
        }

        $result = [
            'completions' => [],
            'suggestions' => []
        ];

        if (!is_string($term)) {
            $result['errors'] = ['term has to be a string'];
            $this->view->assign('value', $result);
            return;
        }

        $requestJson = $this->buildRequestForTerm($term, $contextNodeIdentifier, $dimensionCombination);

        try {
            $response = $this->elasticSearchClient->getIndex()->request('POST', '/_search', [], $requestJson)->getTreatedContent();
            $result['completions'] = $this->extractCompletions($response);
            $result['suggestions'] = $this->extractSuggestions($response);
        } catch (\Exception $e) {
            $result['errors'] = ['Could not execute query: ' . $e->getMessage()];
        }

        $this->view->assign('value', $result);
    }

    /**
     * @throws QueryBuildingException|CacheException|IllegalObjectTypeException
     */
    protected function buildRequestForTerm(string $term, string $contextNodeIdentifier, string $dimensionCombination = null): string
    {
        $cacheKey = $contextNodeIdentifier . '-' . md5($dimensionCombination);
        $termPlaceholder = '---term-soh2gufuNi---';
        $term = strtolower($term);

        // The suggest function only works well with one word
        // special search characters are escaped
        $suggestTerm = SearchTerm::sanitize(explode(' ', $term)[0]);

        if (!$this->elasticSearchQueryTemplateCache->has($cacheKey)) {
            $contentContext = $this->createContentContext('live', $dimensionCombination ? json_decode($dimensionCombination, true) : []);
            $contextNode = $contentContext->getNodeByIdentifier($contextNodeIdentifier);

            if (!$contextNode instanceof NodeInterface) {
                throw new \RuntimeException(sprintf('The context node for search with identifier %s could not be found', $contextNodeIdentifier), 1634467679);
            }

            $sourceFields = array_filter($this->searchAsYouTypeSettings['suggestions']['sourceFields'] ?? ['neos_path']);

            /** @var ElasticSearchQueryBuilder $query */
            $query = $this->elasticSearchQueryBuilder
                ->query($contextNode)
                ->queryFilter('prefix', [
                    'neos_completion' => $termPlaceholder
                ])
                ->limit(0);

            if (($this->searchAsYouTypeSettings['autocomplete']['enabled'] ?? false) === true) {
                // Based on recommendations from https://www.elastic.co/guide/en/elasticsearch/reference/7.2/search-as-you-type.html
                $query
                    ->limit($this->searchAsYouTypeSettings['autocomplete']['size'] ?? 10)
                    ->getRequest()->setValueByPath('query.bool.filter.bool.must', [
                        'multi_match' => [
                            'fields' => [
                                'neos_completion',
                                'neos_completion._2gram',
                                'neos_completion._3gram',
                            ],
                            'type' => 'bool_prefix',
                            'query' => $termPlaceholder,
                        ]
                    ]);
            }

            if (($this->searchAsYouTypeSettings['suggestions']['enabled'] ?? false) === true) {
                $query->suggestions('suggestions', [
                    'prefix' => $termPlaceholder,
                    'completion' => [
                        'field' => 'neos_suggestion',
                        'fuzzy' => true,
                        'size' => $this->searchAsYouTypeSettings['suggestions']['size'] ?? 10,
                        'contexts' => [
                            'suggestion_context' => $this->suggestionContext->buildForSearch($contextNode)->getContextIdentifier(),
                        ]
                    ]
                ]);
            }

            $request = $query->getRequest()->toArray();

            $request['_source'] = $sourceFields;

            $requestTemplate = json_encode($request);

            $this->elasticSearchQueryTemplateCache->set($contextNodeIdentifier, $requestTemplate);
        } else {
            $requestTemplate = $this->elasticSearchQueryTemplateCache->get($cacheKey);
        }

        return str_replace($termPlaceholder, $suggestTerm, $requestTemplate);
    }

    /**
     * Extract autocomplete options
     */
    protected function extractCompletions(array $response): array
    {
        return array_values(array_unique(array_map(static function ($option) {
            return $option['_source']['title'];
        }, $response['hits']['hits'] ?? [])));
    }

    /**
     * Extract suggestion options
     */
    protected function extractSuggestions(array $response): array
    {
        $suggestionOptions = $response['suggest']['suggestions'][0]['options'] ?? [];

        if (empty($suggestionOptions)) {
            return [];
        }

        return array_map(static function ($option) {
            return $option['_source'];
        }, $suggestionOptions);
    }
}
