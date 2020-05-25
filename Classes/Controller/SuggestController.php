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
use Neos\Cache\Frontend\VariableFrontend;
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
     */
    public function indexAction($term, $contextNodeIdentifier, $dimensionCombination = null)
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
     * @param string $dimensionCombination
     * @return string
     * @throws QueryBuildingException
     * @throws \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
     */
    protected function buildRequestForTerm($term, $contextNodeIdentifier, $dimensionCombination = null): string
    {
        $cacheKey = $contextNodeIdentifier . '-' . md5($dimensionCombination);
        $termPlaceholder = '---term-soh2gufuNi---';
        $term = strtolower($term);

        // The suggest function only works well with one word
        // and the term is trimmed to alnum characters to avoid errors
        $suggestTerm = preg_replace('/[[:^alnum:]]/', '', explode(' ', $term)[0]);

        if (!$this->elasticSearchQueryTemplateCache->has($cacheKey)) {
            $contentContext = $this->createContentContext('live', $dimensionCombination ? json_decode($dimensionCombination, true) : []);
            $contextNode = $contentContext->getNodeByIdentifier($contextNodeIdentifier);

            $sourceFields = $this->searchAsYouTypeSettings['suggestions']['sourceFields'] ?? ['neos_path'];

            /** @var ElasticSearchQueryBuilder $query */
            $query = $this->elasticSearchQueryBuilder
                ->query($contextNode)
                ->queryFilter('prefix', [
                    '__completion' => $termPlaceholder
                ])
                ->limit(0)
                ->aggregation('autocomplete', [
                    'terms' => [
                        'field' => '__completion',
                        'order' => [
                            '_count' => 'desc'
                        ],
                        'include' => $termPlaceholder . '.*',
                        'size' => $this->searchAsYouTypeSettings['autocomplete']['size'] ?? 10
                    ]
                ])
                ->suggestions('suggestions', [
                    'prefix' => $termPlaceholder,
                    'completion' => [
                        'field' => '__suggestions',
                        'fuzzy' => true,
                        'size' => $this->searchAsYouTypeSettings['suggestions']['size'] ?? 10,
                        'contexts' => [
                            'parentPath' => $contextNode->getPath(),
                            'workspace' => 'live',
                            'dimensionCombinationHash' => md5(json_encode($contextNode->getContext()->getDimensions())),
                        ]
                    ]
                ]);

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
     * @param $response
     * @return array
     */
    protected function extractCompletions($response)
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
