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

use Flowpack\ElasticSearch\ContentRepositoryAdaptor\ElasticSearchClient;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Mvc\View\JsonView;

class SuggestController extends ActionController
{
    /**
     * @Flow\Inject
     * @var ElasticSearchClient
     */
    protected $elasticSearchClient;

    /**
     * @var array
     */
    protected $viewFormatToObjectNameMap = [
        'json' => JsonView::class
    ];

    /**
     * @param string $term
     *
     * @return void
     */
    public function indexAction($term)
    {
        $request = [
            'suggests' => [
                'text' => $term,
                'term' => [
                    'field' => '_all'
                ]
            ]
        ];

        $response = $this->elasticSearchClient->getIndex()->request('POST', '/_suggest', [], json_encode($request))->getTreatedContent();
        $suggestions = array_map(function ($option) {
            return $option['text'];
        }, $response['suggests'][0]['options']);

        $this->view->assign('value', $suggestions);
    }
}
