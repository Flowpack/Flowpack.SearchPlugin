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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * Class AjaxSearchController
 */
class AjaxSearchController extends ActionController
{
    /**
     * Override the default view from the ActionController to output TypoScript directly
     *
     * @var string
     * @api
     */
    protected $defaultViewObjectName = \TYPO3\Neos\View\TypoScriptView::class;

    /**
     * @param NodeInterface $node
     * @return void
     */
    public function searchAction(NodeInterface $node)
    {
        /* @var $view \TYPO3\Neos\View\TypoScriptView */
        $view = $this->view;
        $view->setTypoScriptPath('ajaxSearch');
        $view->assign('value', $node);
    }
}
