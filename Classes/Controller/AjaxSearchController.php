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

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Neos\View\FusionView;

class AjaxSearchController extends ActionController
{
    /**
     * Override the default view from the ActionController to output Fusion directly
     *
     * @var string
     * @api
     */
    protected $defaultViewObjectName = FusionView::class;

    /**
     * @param NodeInterface $node
     *
     * @return void
     */
    public function searchAction(NodeInterface $node)
    {
        /* @var FusionView $view */
        $view = $this->view;
        $view->setFusionPath('ajaxSearch');
        $view->assign('value', $node);
    }
}
