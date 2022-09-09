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

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;

/**
 * Additional Array Helpers which might once
 *
 * @Flow\Proxy(false)
 */
class SearchArrayHelper implements ProtectedContextAwareInterface
{
    /**
     * Concatenate arrays or values to a new array
     *
     * @param array|mixed $arrays First array or value
     */
    public function flatten($arrays): array
    {
        $return = [];
        if (is_array($arrays)) {
            array_walk_recursive($arrays, static function ($a) use (&$return) {
                $return[] = $a;
            });
        }

        return $return;
    }

    /**
     * All methods are considered safe
     */
    public function allowsCallOfMethod($methodName): bool
    {
        return true;
    }
}
