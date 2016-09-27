<?php
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
 * Helper for building suggestion configurations
 *
 * @Flow\Proxy(false)
 */
class SuggestionIndexHelper implements ProtectedContextAwareInterface
{

    /**
     * @param string $input
     * @param array $payload
     * @param int $weight
     * @return array
     */
    public function buildConfig($input, array $payload = [], $weight = 1)
    {
        return [
            'input' => $this->prepareInput($input),
            'output' => $this->prepareOutput($input),
            'payload' => json_encode($payload),
            'weight' => $weight
        ];
    }

    /**
     * @param string $input
     * @return array
     */
    protected function prepareInput($input)
    {
        return array_filter(explode(' ', preg_replace("/[^[:alnum:][:space:]]/u", ' ', strip_tags($input))));
    }

    /**
     * @param string $input
     * @return array
     */
    protected function prepareOutput($input)
    {
        return strip_tags($input);
    }

    /**
     * All methods are considered safe
     *
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
