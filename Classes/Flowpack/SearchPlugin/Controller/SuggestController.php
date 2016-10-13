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
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

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
        'json' => 'TYPO3\Flow\Mvc\View\JsonView'
    ];

    /**
     * @param NodeInterface $contextNode
     * @param string $term
     */
    public function indexAction(NodeInterface $contextNode, $term)
    {
        /** @var ElasticSearchQueryBuilder $query */
        $query = $this->elasticSearchQueryBuilder->query($contextNode);
        /** @var ElasticSearchQueryResult $result */
        $result = $query
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
                        'workspace' => array_unique(['live', $contextNode->getContext()->getWorkspace()->getName()]),
                        'dimensionCombinationHash' => md5(json_encode($contextNode->getContext()->getDimensions())),
                    ]
                ]
            ])
            ->execute();

        $aggregations = $result->getAggregations();
        $aggregationOptions = $aggregations['autocomplete']['buckets'];

        if (count($aggregationOptions) > 0) {
            $options = array_map(function ($option) {
                return $option['key'];
            }, $aggregationOptions);
        } else {
            $suggestions = $result->getSuggestions();
            $options = array_map(function ($option) {
                return $option['text'];
            }, $suggestions['options']);
        }

        $this->view->assign('value', $options);
    }
}
