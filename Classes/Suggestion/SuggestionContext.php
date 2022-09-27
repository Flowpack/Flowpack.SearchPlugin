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

use Neos\ContentRepository\Domain\Model\NodeInterface;

class SuggestionContext implements SuggestionContextInterface
{

    /**
     * Rhe length of '/sites/'
     * @var int
     */
    protected const SITES_OFFSET = 7;

    protected array $contextValues = [];

    public function buildForIndex(NodeInterface $node): SuggestionContextInterface
    {
        $this->contextValues = [
            'siteName' => $this->getSiteName($node),
            'workspace' => $node->getWorkspace()->getName(),
            'isHidden' => $node->isHidden() ? 'hidden' : 'visible',
        ];

        return $this;
    }

    public function buildForSearch(NodeInterface $node): SuggestionContextInterface
    {
        $this->contextValues = [
            'siteName' => $this->getSiteName($node),
            'workspace' => $node->getWorkspace()->getName(),
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

    protected function getSiteName(NodeInterface $node): string
    {
        return substr($node->getPath(), self::SITES_OFFSET, strpos($node->getPath() . '/', '/', self::SITES_OFFSET) - self::SITES_OFFSET);
    }
}
