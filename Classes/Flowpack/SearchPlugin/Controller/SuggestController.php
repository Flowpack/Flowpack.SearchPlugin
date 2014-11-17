<?php
namespace Flowpack\SearchPlugin\Controller;

/*                                                                                                  *
 * This script belongs to the TYPO3 Flow package "Flowpack.SearchPlugin".                           *
 *                                                                                                  *
 * It is free software; you can redistribute it and/or modify it under                              *
 * the terms of the GNU Lesser General Public License, either version 3                             *
 *  of the License, or (at your option) any later version.                                          *
 *                                                                                                  *
 * The TYPO3 project - inspiring people to share!                                                   *
 *                                                                                                  */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * Class SuggestController
 *
 * @author Jon Klixbüll Langeland <jon@moc.net>
 * @package Flowpack\SearchPlugin\Controller
 */
class SuggestController extends ActionController {

	/**
	 * @Flow\Inject
	 * @var \Flowpack\ElasticSearch\ContentRepositoryAdaptor\ElasticSearchClient
	 */
	protected $elasticSearchClient;

	/**
	 * The node inside which searching should happen
	 *
	 * @var NodeInterface
	 */
	protected $contextNode;

	/**
	 * @Flow\Inject
	 * @var \Flowpack\ElasticSearch\ContentRepositoryAdaptor\LoggerInterface
	 */
	protected $logger;

	/**
	 * @var boolean
	 */
	protected $logThisQuery = FALSE;

	/**
	 * @var string
	 */
	protected $logMessage;

	/**
	 * @var array
	 */
	protected $viewFormatToObjectNameMap = array(
		'json' => 'TYPO3\Flow\Mvc\View\JsonView'
	);

	/**
	 * @param NodeInterface $node
	 * @param string $term
	 */
	public function indexAction(NodeInterface $node, $term) {
		$request = array(
			'suggests' => array(
				'text' => $term,
				'term' => array(
					'field' => '_all'
				)
			)
		);

		$response = $this->elasticSearchClient->getIndex()->request('GET', '/_suggest', array(), json_encode($request))->getTreatedContent();
		$suggestions = array_map(function($option) {
			return $option['text'];
		}, $response['suggests'][0]['options']);

		$this->view->assign('value', $suggestions);
	}

}