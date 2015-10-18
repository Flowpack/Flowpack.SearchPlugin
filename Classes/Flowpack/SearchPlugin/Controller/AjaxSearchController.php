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
 * Class AjaxSearchController
 *
 * @author Sebastian Kurfürst <sk@sandstorm.de>
 * @author Bastian Heist <bh@sandstorm.de>
 * @package Flowpack\SearchPlugin\Controller
 */
class AjaxSearchController extends ActionController {

	/**
	 * Override the default view from the ActionController to output TypoScript directly
	 *
	 * @var string
	 * @api
	 */
	protected $defaultViewObjectName = \TYPO3\Neos\View\TypoScriptView::class;

	/**
	 * @param NodeInterface $node
	 */
	public function searchAction(NodeInterface $node) {
		/* @var $view \TYPO3\Neos\View\TypoScriptView */
		$view = $this->view;
		$view->setTypoScriptPath('ajaxSearch');
		$view->assign('value', $node);
	}

}