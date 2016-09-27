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

use TYPO3\Eel\ProtectedContextAwareInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * Helper for building suggestion configurations
 *
 * @Flow\Proxy(false)
 */
class SuggestionHelper implements ProtectedContextAwareInterface
{

    /**
     * @param string $text
     * @param NodeInterface $node
     * @param int $weight
     * @return array
     */
    public function buildConfig($text, NodeInterface $node, $weight = 1)
    {
        $text = strip_tags($text);

        return [
            'input' => explode(' ', preg_replace("/[^[:alnum:][:space:]]/u", " ", $text)),
            'output' => $text,
//            'payload' => [
//                'nodeIdentifier' => $node->getIdentifier(),
//            ],
            'weight' => $weight,
        ];
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
