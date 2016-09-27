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
use Flowpack\ElasticSearch\ContentRepositoryAdaptor\Eel\ElasticSearchQueryResult;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Mvc\View\JsonView;

/**
 * Class SuggestController
 */
class SuggestController extends ActionController
{
    /**
     * @Flow\Inject
     * @var ElasticSearchQueryBuilder
     */
    protected $elasticSearchQueryBuilder;

    /**
     * @var array
     */
    protected $viewFormatToObjectNameMap = [
        'json' => JsonView::class
    ];

    /**
     * @param NodeInterface $contextNode
     * @param string $term
     * @return void
     */
    public function indexAction(NodeInterface $contextNode, $term)
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

        $term = strtolower($term);

        // TODO: cache query by node identifier

        /** @var ElasticSearchQueryBuilder $query */
        $query = $this->elasticSearchQueryBuilder->query($contextNode);
        $query
            ->queryFilter('prefix', [
                '__completion' => $term
            ])
            ->limit(0)
            ->aggregation('autocomplete', [
                'terms' => [
                    'field' => '__completion',
                    'order' => [
                        '_count' => 'desc'
                    ],
                    'include' => [
                        'pattern' => $term . '.*'
                    ]
                ]
            ])
            ->suggestions('suggestions', [
                'text' => $term,
                'completion' => [
                    'field' => '__suggestions',
                    'fuzzy' => true,
                    'context' => [
                        'parentPath' => $contextNode->getPath(),
                        'workspace' => 'live',
                        'dimensionCombinationHash' => md5(json_encode($contextNode->getContext()->getDimensions())),
                    ]
                ]
            ]);

        try {
            /** @var ElasticSearchQueryResult $queryResult */
            $queryResult = $query->execute();
        } catch (\Exception $e) {
            $result['errors'] = ['Could not execute query'];
            $this->view->assign('value', $result);
            return;
        }

        $aggregations = $queryResult->getAggregations();

        // Extract autocomplete options
        $autoCompletionOptions = array_map(function ($option) {
            return $option['key'];
        }, $aggregations['autocomplete']['buckets']);
        $result['completions'] = $autoCompletionOptions;

        // Extract suggestion options
        $suggestionOptions = $queryResult->getSuggestions();
        if (count($suggestionOptions['suggestions'][0]['options']) > 0) {
            $result['suggestions'] = $suggestionOptions['suggestions'][0]['options'];
        }

        $this->view->assign('value', $result);
    }
}
