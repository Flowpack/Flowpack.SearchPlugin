<?php
declare(strict_types=1);

namespace Flowpack\SearchPlugin\Service;

/*
 * This file is part of the Flowpack.SearchPlugin package.
 *
 * (c) Contributors of the Flowpack Team - flowpack.org
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\BaseUriProvider;
use Neos\Flow\Http\ServerRequestAttributes;
use Neos\Flow\Mvc;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Utility\ObjectAccess;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriFactoryInterface;

/**
 * @Flow\Scope("singleton")
 */
class ContextBuilder
{
    /**
     * @Flow\InjectConfiguration(path="urlSchemaAndHost")
     * @var string
     */
    protected $urlSchemeAndHostFromConfiguration;

    /**
     * @Flow\Inject
     * @var UriFactoryInterface
     */
    protected $uriFactory;

    /**
     * @Flow\Inject
     * @var ServerRequestFactoryInterface
     */
    protected $requestFactory;

    /**
     * @var ControllerContext
     */
    protected $controllerContext;

    /**
     * @Flow\Inject
     * @var BaseUriProvider
     */
    protected $baseUriProvider;

    /**
     * @var ServerRequestInterface
     */
    protected $httpRequest = null;

    public function initializeObject(): void
    {
        // we want to have nice URLs
        putenv('FLOW_REWRITEURLS=1');
    }

    public static function buildControllerContextFromActionRequest(ActionRequest $actionRequest): ControllerContext
    {
        return new ControllerContext(
            $actionRequest,
            new Mvc\ActionResponse(),
            new Mvc\Controller\Arguments([]),
            self::getUriBuilderFromActionRequest($actionRequest)
        );
    }

    /**
     * @param string $urlSchemaAndHost Can be set if known (eg from the primary domain retrieved from the domain repository)
     */
    public function buildControllerContext(string $urlSchemaAndHost = ''): ControllerContext
    {
        if ($this->controllerContext instanceof ControllerContext) {
            return $this->controllerContext;
        }

        $this->setBaseUriInBaseUriProviderIfNotSet($urlSchemaAndHost);

        if ($urlSchemaAndHost === '') {
            $urlSchemaAndHost = $this->urlSchemeAndHostFromConfiguration;
        }
        $requestUri = $this->uriFactory->createUri($urlSchemaAndHost);

        $httpRequest = $this->httpRequest ?? $this->requestFactory->createServerRequest('get', $requestUri);
        $parameters = $httpRequest->getAttribute(ServerRequestAttributes::ROUTING_PARAMETERS) ?? RouteParameters::createEmpty();
        $httpRequest = $httpRequest->withAttribute(ServerRequestAttributes::ROUTING_PARAMETERS, $parameters->withParameter('requestUriHost', $requestUri->getHost()));

        $actionRequest = ActionRequest::fromHttpRequest($httpRequest);
        $actionRequest->setFormat('html');

        $this->controllerContext = self::buildControllerContextFromActionRequest($actionRequest);
        return $this->controllerContext;
    }

    public function setHttpRequest(ServerRequestInterface $httpRequest): self
    {
        $this->httpRequest = $httpRequest;
        return $this;
    }

    protected function setBaseUriInBaseUriProviderIfNotSet(string $urlSchemaAndHost = ''): void
    {
        $urlSchemaAndHost = $urlSchemaAndHost === '' ? '/' : $urlSchemaAndHost;

        try {
            $this->baseUriProvider->getConfiguredBaseUriOrFallbackToCurrentRequest();
        } catch (\Exception $exception) {
            ObjectAccess::setProperty($this->baseUriProvider, 'configuredBaseUri', $urlSchemaAndHost, true);
        }
    }

    private static function getUriBuilderFromActionRequest(ActionRequest $actionRequest): UriBuilder
    {
        $uriBuilder = new UriBuilder();
        $uriBuilder->setRequest($actionRequest);
        return $uriBuilder;
    }
}
