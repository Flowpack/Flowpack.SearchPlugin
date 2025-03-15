<?php
declare(strict_types=1);

namespace Flowpack\SearchPlugin\Suggestion;

/*
 * This file is part of the Flowpack.SearchPlugin package.
 *
 * (c) Contributors of the Flowpack Team - flowpack.org
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\NodeType\NodeTypeNames;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindClosestNodeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\NodeTypeCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;
use Neos\Neos\Domain\SubtreeTagging\NeosSubtreeTag;

class SuggestionContext implements SuggestionContextInterface
{
    /**
     * @var array
     */
    protected $contextValues = [];

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    public function buildForIndex(Node $node): SuggestionContextInterface
    {
        $this->contextValues = [
            'siteName' => $this->getSiteName($node),
            'workspace' => $node->workspaceName->value,
            'isHidden' => $node->tags->contain(NeosSubtreeTag::disabled()) ? 'hidden' : 'visible',
        ];

        return $this;
    }

    public function buildForSearch(Node $node): SuggestionContextInterface
    {
        $this->contextValues = [
            'siteName' => $this->getSiteName($node),
            'workspace' => $node->workspaceName->value,
            'isHidden' => 'visible',
        ];

        return $this;
    }

    public function getContextIdentifier(): string
    {
        return implode('_', $this->contextValues);
    }

    public function __toString()
    {
        return $this->getContextIdentifier();
    }

    /**
     * @param Node $node
     * @return string
     */
    protected function getSiteName(Node $node): string
    {
        $subgraph = $this->contentRepositoryRegistry->subgraphForNode($node);
        $siteNode = $subgraph->findClosestNode($node->aggregateId, FindClosestNodeFilter::create(
            NodeTypeCriteria::createWithAllowedNodeTypeNames(
                NodeTypeNames::with(
                    NodeTypeNameFactory::forSite()
                )
            )
        ));

        return $siteNode->name->value;
    }
}
