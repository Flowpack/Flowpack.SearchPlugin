<?php
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
use Neos\Cache\Frontend\VariableFrontend;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Mvc\View\JsonView;
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

    public function initializeObject()
    {
        if ($this->objectManager->isRegistered(ElasticSearchClient::class)) {
            $this->elasticSearchClient = $this->objectManager->get(ElasticSearchClient::class);
            $this->elasticSearchQueryBuilder = $this->objectManager->get(ElasticSearchQueryBuilder::class);
        }
    }

    /**
     * @param string $term
     * @param string $contextNodeIdentifier
     * @param string $dimensionCombination
     * @return void
     * @throws QueryBuildingException
     * @throws \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
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
     * @param string $term
     * @param string $contextNodeIdentifier
     * @param string|null $dimensionCombination
     * @return string
     * @throws QueryBuildingException
     * @throws \Neos\Cache\Exception
     * @throws \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
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
                throw new \Exception(sprintf('The context node for search with identifier %s could not be found', $contextNodeIdentifier), 1634467679);
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
                $query->aggregation('autocomplete', [
                    'terms' => [
                        'field' => 'neos_completion',
                        'order' => [
                            '_count' => 'desc'
                        ],
                        'include' => $termPlaceholder . '.*',
                        'size' => $this->searchAsYouTypeSettings['autocomplete']['size'] ?? 10
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
                            'suggestion_context' => $this->suggestionContext->buildForSearch($contextNode)->getContextIdentifier()
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
     *
     * @param array $response
     * @return array
     */
    protected function extractCompletions(array $response): array
    {
        $aggregations = $response['aggregations'] ?? [];

        return array_map(static function ($option) {
            return $option['key'];
        }, $aggregations['autocomplete']['buckets']);
    }

    /**
     * Extract suggestion options
     *
     * @param array $response
     * @return array
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
