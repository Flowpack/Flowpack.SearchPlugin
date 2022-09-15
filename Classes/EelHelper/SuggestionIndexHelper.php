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

use Flowpack\SearchPlugin\Exception;
use Flowpack\SearchPlugin\Suggestion\SuggestionContextInterface;
use Flowpack\SearchPlugin\Utility\SearchTerm;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;

/**
 * Helper for building suggestion configurations
 */
class SuggestionIndexHelper implements ProtectedContextAwareInterface
{

    /**
     * @Flow\Inject
     * @var SuggestionContextInterface
     */
    protected $suggestionContext;

    /**
     * @param string|array $input The input to store, this can be an array of strings or just a string. This field is mandatory.
     * @param int $weight A positive integer or a string containing a positive integer, which defines a weight and allows you to rank your suggestions.
     * @throws Exception
     */
    public function build($input, int $weight = 1): array
    {
        return [
            'input' => $this->prepareInput($input),
            'weight' => $weight
        ];
    }

    public function buildContext(NodeInterface $node): string
    {
        return (string)($this->suggestionContext->buildForIndex($node));
    }

    /**
     * @param string|array $input
     * @throws Exception
     */
    protected function prepareInput($input): ?array
    {
        $process = static function (?string $input) {
            $input = preg_replace("/\r|\n/", '', $input);
            return array_values(array_filter(explode(' ', SearchTerm::sanitize(strip_tags($input)))));
        };

        if (\is_string($input)) {
            return $process($input);
        }

        if (\is_array($input)) {
            $data = [];
            foreach (array_map($process, $input) as $values) {
                $data = \array_merge($data, $values);
            }
            return $data;
        }

        throw new Exception('Only string or array are supported as input', 1512733287);
    }

    /**
     * All methods are considered safe
     */
    public function allowsCallOfMethod($methodName): bool
    {
        return true;
    }
}
