<?php
declare(strict_types=1);

namespace Flowpack\SearchPlugin\Utility;

/*
 * This file is part of the Flowpack.SearchPlugin package.
 *
 * (c) Contributors of the Flowpack Team - flowpack.org
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

class SearchTerm
{
    /**
     * According to https://lucene.apache.org/core/3_4_0/queryparsersyntax.html#Escaping%20Special%20Characters,
     * special characters should be escapable using a „\“ character. Sadly because of some internal json encoding this doesn't work properly.
     * Any of these characters break the query and are removed.
     */
    public static function sanitize(string $input): string
    {
        return str_replace(['\\', '=', '>', '<', '(', ')', '{', '}', '[', ']', '^', '"', '~', '*', '?', ':', '/'], '', $input);
    }
}
