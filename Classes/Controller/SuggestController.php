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
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Mvc\View\JsonView;
use Neos\Neos\Controller\CreateContentContextTrait;

class SuggestController extends ActionController
{
    use CreateContentContextTrait;

    /**
     * @Flow\Inject
     * @var \Flowpack\ElasticSearch\ContentRepositoryAdaptor\ElasticSearchClient
     */
    protected $elasticSearchClient;

    /**
     * @Flow\Inject
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

    public function initializeObject()
    {
        if ($this->objectManager->isRegistered(ElasticSearchClient::class)) {
            $this->elasticSearchClient = $this->objectManager->get(ElasticSearchClient::class);
        }
    }

    /**
     * @param string $contextNodeIdentifier
     * @param string $dimensionCombination
     * @param string $term
     * @return void
     */
    public function indexAction($contextNodeIdentifier, $dimensionCombination, $term)
    {
        $result = [
            'completions' => [],
            'suggestions' => []
        ];

        if (!is_string($term)) {
            $result['errors'] = ['term has to be a string'];
            $this->view->assign('value', $result);
            return;
        }

        $requestJson = $this->buildRequestForTerm($contextNodeIdentifier, $dimensionCombination, $term);

        try {
            $response = $this->elasticSearchClient->getIndex()->request('POST', '/_search', [], $requestJson)->getTreatedContent();
            $result['completions'] = $this->extractCompletions($response);
            $result['suggestions'] = $this->extractSuggestions($response);
        } catch (\Exception $e) {
            $result['errors'] = ['Could not execute query'];
        }

        $this->view->assign('value', $result);
    }

    /**
     * @param string $term
     * @param string $contextNodeIdentifier
     * @param string $dimensionCombination
     * @return ElasticSearchQueryBuilder
     */
    protected function buildRequestForTerm($contextNodeIdentifier, $dimensionCombination, $term)
    {
        $cacheKey = $contextNodeIdentifier . '-' . md5($dimensionCombination);
        $termPlaceholder = '---term-soh2gufuNi---';
        $term = strtolower($term);

        // The suggest function only works well with one word
        // and the term is trimmed to alnum characters to avoid errors
        $suggestTerm = preg_replace('/[[:^alnum:]]/', '', explode(' ', $term)[0]);

        if(!$this->elasticSearchQueryTemplateCache->has($cacheKey)) {
            $contentContext = $this->createContentContext('live', json_decode($dimensionCombination, true));
            $contextNode = $contentContext->getNodeByIdentifier($contextNodeIdentifier);

            /** @var ElasticSearchQueryBuilder $query */
            $query = $this->elasticSearchQueryBuilder->query($contextNode);
            $query
                ->queryFilter('prefix', [
                    '__completion' => $termPlaceholder
                ])
                ->limit(1)
                ->aggregation('autocomplete', [
                    'terms' => [
                        'field' => '__completion',
                        'order' => [
                            '_count' => 'desc'
                        ],
                        'include' => [
                            'pattern' => $termPlaceholder . '.*'
                        ]
                    ]
                ])
                ->suggestions('suggestions', [
                    'text' => $termPlaceholder,
                    'completion' => [
                        'field' => '__suggestions',
                        'fuzzy' => true,
                        'size' => 10,
                        'context' => [
                            'parentPath' => $contextNode->getPath(),
                            'workspace' => 'live',
                            'dimensionCombinationHash' => md5(json_encode($contextNode->getContext()->getDimensions())),
                        ]
                    ]
                ]);

            $requestTemplate = $query->getRequest()->getRequestAsJson();

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
        $aggregations = isset($response['aggregations']) ? $response['aggregations'] : [];

        return array_map(function ($option) {
            return $option['key'];
        }, $aggregations['autocomplete']['buckets']);
    }

    /**
     * Extract suggestion options
     *
     * @param $response
     * @return array
     */
    protected function extractSuggestions($response)
    {
        if ($this->elasticSearchClient === null) {
            throw new \RuntimeException('The SuggestController needs an ElasticSearchClient, it seems you run without the flowpack/elasticsearch-contentrepositoryadaptor package, though.', 1487189823);
        }
        $suggestionOptions = isset($response['suggest']) ? $response['suggest'] : [];
        if (count($suggestionOptions['suggestions'][0]['options']) > 0) {
            return $suggestionOptions['suggestions'][0]['options'];
        }
        return [];
    }
}
