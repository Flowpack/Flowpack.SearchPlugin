<?php
declare(strict_types=1);

/*
 * This file is part of the Flowpack.SearchPlugin package.
 *
 * (c) Contributors of the Flowpack Team - flowpack.org
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

namespace Flowpack\SearchPlugin\Suggestion;

use Neos\ContentRepository\Domain\Model\NodeInterface;

interface SuggestionContextInterface
{

    /**
     * Build the context from a given node
     */
    public function buildForIndex(NodeInterface $node): SuggestionContextInterface;

    /**
     * Build the context from a given node
     */
    public function buildForSearch(NodeInterface $node): SuggestionContextInterface;

    /**
     * Returns the calculated context identifier
     */
    public function getContextIdentifier(): string;
}
