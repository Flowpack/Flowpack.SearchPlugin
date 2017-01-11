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

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\ActionController;

/**
 * Class SuggestController
 */
class SuggestController extends ActionController
{
    /**
     * Dynamic dependency; to make the system work with SimpleSearch
     * @var \Flowpack\ElasticSearch\ContentRepositoryAdaptor\ElasticSearchClient
     */
    protected $elasticSearchClient;

    /**
     * @var array
     */
    protected $viewFormatToObjectNameMap = [
        'json' => 'TYPO3\Flow\Mvc\View\JsonView'
    ];

    public function initializeObject()
    {
        if ($this->objectManager->isRegistered('Flowpack\ElasticSearch\ContentRepositoryAdaptor\ElasticSearchClient')) {
            $this->elasticSearchClient = $this->objectManager->get('Flowpack\ElasticSearch\ContentRepositoryAdaptor\ElasticSearchClient');
        }
    }

    /**
     * @param string $term
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
