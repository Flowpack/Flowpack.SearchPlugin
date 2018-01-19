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
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Mvc\View\JsonView;

class SuggestController extends ActionController
{
    /**
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
     * Sets the ElasticSearchClient instance needed for this to work. If no client is set,
     * this controller cannot be used; but at least the package can otherwise be used with
     * e.g. SimpleSearch.
     *
     * @return void
     */
    public function initializeObject()
    {
        if ($this->objectManager->isRegistered(ElasticSearchClient::class)) {
            $this->elasticSearchClient = $this->objectManager->get(ElasticSearchClient::class);
        }
    }

    /**
     * @param string $term
     *
     * @return void
     * @throws \Flowpack\ElasticSearch\Exception
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

        if ($this->elasticSearchClient === null) {
            throw new \RuntimeException('The SuggestController needs an ElasticSearchClient, it seems you run without the flowpack/elasticsearch-contentrepositoryadaptor package, though.', 1487189823);
        }

        $response = $this->elasticSearchClient->getIndex()->request('POST', '/_suggest', [], json_encode($request))->getTreatedContent();
        $suggestions = array_map(function ($option) {
            return $option['text'];
        }, $response['suggests'][0]['options']);

        $this->view->assign('value', $suggestions);
    }
}
