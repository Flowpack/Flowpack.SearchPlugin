<?php

namespace Flowpack\SearchPlugin\Tests\Unit\Controller;

use Flowpack\ElasticSearch\ContentRepositoryAdaptor\Driver\QueryInterface;
use Flowpack\ElasticSearch\ContentRepositoryAdaptor\Eel\ElasticSearchQueryBuilder;
use Flowpack\ElasticSearch\ContentRepositoryAdaptor\ElasticSearchClient;
use Flowpack\ElasticSearch\Domain\Model\Index;
use Flowpack\ElasticSearch\Transfer\Response;
use Flowpack\SearchPlugin\Controller\SuggestController;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Mvc\ActionResponse;
use Neos\Flow\Mvc\View\JsonView;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\Domain\Service\ContentContext;

class SuggestControllerTest extends UnitTestCase
{
    /** @dataProvider missingContextArguments */
    public function testMissingContextReturnsBadRequest(array $arguments): void
    {
        $controller = $this->getMockBuilder(SuggestController::class)
            ->onlyMethods(['createContentContext'])->getMock();
        $controller->expects(self::never())->method('createContentContext');
        $client = $this->createMock(ElasticSearchClient::class);
        $client->expects(self::never())->method('getIndex');
        $cache = $this->createMock(VariableFrontend::class);
        $cache->expects(self::never())->method('has');
        $response = new ActionResponse();
        $view = $this->createMock(JsonView::class);
        $view->expects(self::once())->method('assign')->with('value', [
            'completions' => [],
            'suggestions' => [],
            'errors' => ['contextNodeIdentifier must not be empty']
        ]);
        $this->inject($controller, 'elasticSearchClient', $client);
        $this->inject($controller, 'elasticSearchQueryTemplateCache', $cache);
        $this->inject($controller, 'response', $response);
        $this->inject($controller, 'view', $view);

        $controller->indexAction(...$arguments);

        self::assertSame(400, $response->getStatusCode());
    }

    public function missingContextArguments(): array
    {
        return ['omitted' => [[]], 'empty' => [['test', '', '{}']]];
    }

    public function testNonexistentContextStillReportsMissingNode(): void
    {
        $controller = $this->getMockBuilder(SuggestController::class)
            ->onlyMethods(['createContentContext'])->getMock();
        $context = $this->createMock(ContentContext::class);
        $context->expects(self::once())->method('getNodeByIdentifier')->with('nonexistent')->willReturn(null);
        $controller->expects(self::once())->method('createContentContext')->with('live', [])->willReturn($context);
        $this->inject($controller, 'elasticSearchClient', $this->createMock(ElasticSearchClient::class));
        $this->inject($controller, 'elasticSearchQueryTemplateCache', $this->createMock(VariableFrontend::class));

        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1634467679);
        $this->expectExceptionMessage('The context node for search with identifier nonexistent could not be found');
        $controller->indexAction('test', 'nonexistent', '{}');
    }

    /** @dataProvider validContexts */
    public function testValidContextPreservesSuggestions(bool $warm, string $site, string $dimensions): void
    {
        $controller = $this->getMockBuilder(SuggestController::class)
            ->onlyMethods(['createContentContext'])->getMock();
        $cache = $this->createMock(VariableFrontend::class);
        $cache->expects(self::once())->method('has')->with($site . '-' . md5($dimensions))->willReturn($warm);
        $template = '{"query":{"prefix":"---term-soh2gufuNi---"},"_source":["neos_path"]}';
        if ($warm) {
            $controller->expects(self::never())->method('createContentContext');
            $cache->expects(self::once())->method('get')->with($site . '-' . md5($dimensions))->willReturn($template);
            $cache->expects(self::never())->method('set');
        } else {
            $node = $this->createMock(NodeInterface::class);
            $context = $this->createMock(ContentContext::class);
            $context->expects(self::once())->method('getNodeByIdentifier')->with($site)->willReturn($node);
            $controller->expects(self::once())->method('createContentContext')
                ->with('live', json_decode($dimensions, true))->willReturn($context);
            $query = $this->createMock(QueryInterface::class);
            $query->method('toArray')->willReturn(['query' => ['prefix' => '---term-soh2gufuNi---']]);
            $builder = $this->createMock(ElasticSearchQueryBuilder::class);
            $builder->expects(self::once())->method('query')->with($node)->willReturnSelf();
            $builder->method('queryFilter')->willReturnSelf();
            $builder->method('limit')->willReturnSelf();
            $builder->method('getRequest')->willReturn($query);
            $this->inject($controller, 'elasticSearchQueryBuilder', $builder);
            $cache->expects(self::once())->method('set');
        }
        $index = $this->createMock(Index::class);
        $searchResponse = $this->createMock(Response::class);
        $searchResponse->method('getTreatedContent')->willReturn([
            'aggregations' => ['autocomplete' => ['buckets' => [['key' => 'testing']]]],
            'suggest' => ['suggestions' => [['options' => [['_source' => ['neos_path' => '/sites/example/page']]]]]]
        ]);
        $index->expects(self::once())->method('request')->with(
            'POST',
            '/_search',
            [],
            '{"query":{"prefix":"test"},"_source":["neos_path"]}'
        )->willReturn($searchResponse);
        $client = $this->createMock(ElasticSearchClient::class);
        $client->method('getIndex')->willReturn($index);
        $view = $this->createMock(JsonView::class);
        $view->expects(self::once())->method('assign')->with('value', [
            'completions' => ['testing'], 'suggestions' => [['neos_path' => '/sites/example/page']]
        ]);
        $response = new ActionResponse();
        $this->inject($controller, 'elasticSearchClient', $client);
        $this->inject($controller, 'elasticSearchQueryTemplateCache', $cache);
        $this->inject($controller, 'response', $response);
        $this->inject($controller, 'view', $view);

        $controller->indexAction('TEST second', $site, $dimensions);

        self::assertSame(200, $response->getStatusCode());
    }

    public function validContexts(): array
    {
        return [
            'cold first site de' => [false, 'site-a', '{"language":["de"]}'],
            'warm first site de' => [true, 'site-a', '{"language":["de"]}'],
            'cold second site en' => [false, 'site-b', '{"language":["en"]}'],
            'warm second site en' => [true, 'site-b', '{"language":["en"]}'],
        ];
    }
}
