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

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n\Exception\InvalidLocaleIdentifierException;
use Neos\Flow\I18n\Locale;
use Neos\Flow\I18n\Service;
use Neos\Flow\Log\ThrowableStorageInterface;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Fusion\Core\Runtime;
use Neos\Fusion\Exception as FusionException;
use Neos\Neos\Controller\CreateContentContextTrait;
use Neos\Neos\Domain\Exception;
use Neos\Neos\Domain\Service\FusionService;
use Psr\Log\LoggerInterface;

class FusionRenderingService
{
    use CreateContentContextTrait;

    /**
     * @Flow\Inject
     * @var Service
     */
    protected $i18nService;

    /**
     * @var Runtime
     */
    protected $fusionRuntime;

    /**
     * @Flow\Inject
     * @var FusionService
     */
    protected $fusionService;

    /**
     * @Flow\Inject
     * @var ContextBuilder
     */
    protected $contextBuilder;

    /**
     * @var array
     */
    protected $options = ['enableContentCache' => true];

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @Flow\Inject
     * @var ThrowableStorageInterface
     */
    protected $throwableStorage;

    /**
     * @throws FusionException
     * @throws Exception
     */
    public function render(NodeInterface $node, string $fusionPath, array $contextData = [])
    {
        $dimensions = $node->getDimensions();
        $context = $this->createContextMatchingNodeData($node->getNodeData());

        $node = $context->getNodeByIdentifier($node->getIdentifier());

        $currentSiteNode = $context->getCurrentSiteNode();

        if (!$currentSiteNode instanceof NodeInterface) {
            $this->logger->error(sprintf('Could not get the current site node for node "%s". Rendering skipped.', (string)$node), LogEnvironment::fromMethodName(__METHOD__));
            return '';
        }

        $fusionRuntime = $this->getFusionRuntime($currentSiteNode);

        if (array_key_exists('language', $dimensions) && $dimensions['language'] !== []) {
            try {
                $currentLocale = new Locale($dimensions['language'][0]);
                $this->i18nService->getConfiguration()->setCurrentLocale($currentLocale);
                $this->i18nService->getConfiguration()->setFallbackRule([
                    'strict' => false,
                    'order' => array_reverse($dimensions['language'])
                ]);
            } catch (InvalidLocaleIdentifierException $exception) {
                $logMessage = $this->throwableStorage->logThrowable($exception);
                $this->logger->error($logMessage, LogEnvironment::fromMethodName(__METHOD__));
            }
        }

        $fusionRuntime->pushContextArray(array_merge([
            'node' => $node,
            'documentNode' => $this->getClosestDocumentNode($node) ?: $node,
            'site' => $currentSiteNode,
            'editPreviewMode' => null,
        ], $contextData));

        try {
            $output = $fusionRuntime->render($fusionPath);
            $fusionRuntime->popContext();
            return $output;
        } catch (\Exception $exception) {
            $logMessage = $this->throwableStorage->logThrowable($exception);
            $this->logger->error($logMessage, LogEnvironment::fromMethodName(__METHOD__));
        }

        return '';
    }

    /**
     * @throws FusionException
     * @throws Exception
     */
    public function renderByIdentifier(string $nodeIdentifier, string $fusionPath, string $workspace = 'live', array $contextData = [])
    {
        $context = $this->createContentContext($workspace);
        $node = $context->getNodeByIdentifier($nodeIdentifier);
        if ($node !== null) {
            return $this->render($node, $fusionPath, $contextData);
        }
        return '';
    }

    /**
     * @throws FusionException
     * @throws Exception
     */
    protected function getFusionRuntime(NodeInterface $currentSiteNode): Runtime
    {
        if ($this->fusionRuntime === null) {
            $this->fusionRuntime = $this->fusionService->createRuntime($currentSiteNode, $this->contextBuilder->buildControllerContext());

            if (isset($this->options['enableContentCache']) && $this->options['enableContentCache'] !== null) {
                $this->fusionRuntime->setEnableContentCache($this->options['enableContentCache']);
            }
        }

        return $this->fusionRuntime;
    }

    protected function getClosestDocumentNode(NodeInterface $node): NodeInterface
    {
        while ($node !== null && !$node->getNodeType()->isOfType('Neos.Neos:Document')) {
            $node = $node->getParent();
        }

        return $node;
    }
}
