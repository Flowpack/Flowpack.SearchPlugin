<?php
declare(strict_types=1);

namespace Flowpack\SearchPlugin\Eel;

/*
 * This file is part of the Flowpack.SearchPlugin package.
 *
 * (c) Contributors of the Flowpack Team - flowpack.org
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Flowpack\SearchPlugin\Service\FusionRenderingService;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Exception\InvalidActionNameException;
use Neos\Flow\Mvc\Exception\InvalidArgumentNameException;
use Neos\Flow\Mvc\Exception\InvalidArgumentTypeException;
use Neos\Flow\Mvc\Exception\InvalidControllerNameException;
use Neos\Fusion\Exception as FusionException;
use Neos\Neos\Domain\Exception as DomainException;

class FusionRenderingHelper implements ProtectedContextAwareInterface
{
    /**
     * @Flow\Inject
     * @var FusionRenderingService
     */
    protected $fusionRenderingService;

    /**
     * @throws InvalidActionNameException
     * @throws InvalidArgumentNameException
     * @throws InvalidArgumentTypeException
     * @throws InvalidControllerNameException
     * @throws FusionException
     * @throws DomainException
     */
    public function render(NodeInterface $node, string $fusionPath)
    {
        return $this->fusionRenderingService->render($node, $fusionPath);
    }

    /**
     * @throws InvalidActionNameException
     * @throws InvalidArgumentNameException
     * @throws InvalidArgumentTypeException
     * @throws InvalidControllerNameException
     * @throws FusionException
     * @throws DomainException
     */
    public function renderByIdentifier(string $nodeIdentifier, string $fusionPath, string $workspace = 'live', array $contextData = [])
    {
        return $this->fusionRenderingService->renderByIdentifier($nodeIdentifier, $fusionPath, $workspace, $contextData);
    }

    /**
     * @param string $methodName
     */
    public function allowsCallOfMethod($methodName): bool
    {
        return true;
    }

}
