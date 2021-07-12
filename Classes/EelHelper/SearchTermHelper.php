<?php
declare(strict_types=1);

namespace Flowpack\SearchPlugin\EelHelper;

/*
 * This file is part of the Flowpack.SearchPlugin package.
 *
 * (c) Contributors of the Flowpack Team - flowpack.org
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Flowpack\SearchPlugin\Utility\SearchTerm;
use Neos\Eel\ProtectedContextAwareInterface;

class SearchTermHelper implements ProtectedContextAwareInterface
{

    public function sanitize(string $term): string
    {
        return SearchTerm::sanitize($term);
    }

    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
