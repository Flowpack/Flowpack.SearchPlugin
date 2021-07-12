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
    public static function sanitize(string $input): string
    {
        return str_replace(['=', '>', '<', '(', ')', '{', '}', '[', ']', '^', '"', '~', '*', '?', ':', '\\', '/'], ['', '', '', '(', '\)', '\{', '\}', '\[', '\]', '\^', '\"', '\~', '\*', '\?', '\:', '\\\\', '\/'], $input);
    }
}
