<?php

declare(strict_types=1);

namespace MethorZ\RequestLogger\Tests\Unit\Middleware;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use MethorZ\RequestLogger\Middleware\LoggingMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

final class LoggingMiddlewareTest extends TestCase
{
    public function testLogsRequestStartAndEnd(): void
    {
        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockLogger->expects($this->exactly(2))
            ->method('info');

        $middleware = new LoggingMiddleware($mockLogger);

        $request = new ServerRequest([], [], '/test', 'GET');
        $handler = $this->createMockHandler(new Response());

        $response = $middleware->process($request, $handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testAddsRequestIdToResponseHeader(): void
    {
        $mockLogger = $this->createMock(LoggerInterface::class);
        $middleware = new LoggingMiddleware($mockLogger);

        $request = new ServerRequest([], [], '/test', 'GET');
        $handler = $this->createMockHandler(new Response());

        $response = $middleware->process($request, $handler);

        $this->assertTrue($response->hasHeader('X-Request-ID'));
        $this->assertNotEmpty($response->getHeaderLine('X-Request-ID'));
    }

    public function testDoesNotAddRequestIdHeaderWhenDisabled(): void
    {
        $mockLogger = $this->createMock(LoggerInterface::class);
        $middleware = new LoggingMiddleware($mockLogger, null, false);

        $request = new ServerRequest([], [], '/test', 'GET');
        $handler = $this->createMockHandler(new Response());

        $response = $middleware->process($request, $handler);

        $this->assertFalse($response->hasHeader('X-Request-ID'));
    }

    public function testLogsExceptionWhenHandlerThrows(): void
    {
        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockLogger->expects($this->once())
            ->method('error')
            ->with(
                'Request failed with exception',
                $this->callback(function (array $context): bool {
                    $this->assertArrayHasKey('exception', $context);
                    $this->assertArrayHasKey('exception_class', $context);
                    $this->assertArrayHasKey('request_id', $context);

                    return true;
                }),
            );

        $middleware = new LoggingMiddleware($mockLogger);

        $request = new ServerRequest([], [], '/test', 'GET');
        $handler = $this->createThrowingHandler();

        $this->expectException(\RuntimeException::class);

        $middleware->process($request, $handler);
    }

    public function testLogsRequestMethodAndUri(): void
    {
        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockLogger->expects($this->exactly(2))
            ->method('info')
            ->willReturnCallback(function (string $message, array $context): void {
                if ($message === 'Request started') {
                    $this->assertArrayHasKey('method', $context);
                    $this->assertArrayHasKey('uri', $context);
                    $this->assertSame('POST', $context['method']);
                    $this->assertStringContainsString('/api/test', $context['uri']);
                }
            });

        $middleware = new LoggingMiddleware($mockLogger);

        $request = new ServerRequest([], [], '/api/test', 'POST');
        $handler = $this->createMockHandler(new Response());

        $middleware->process($request, $handler);
    }

    private function createMockHandler(ResponseInterface $response): RequestHandlerInterface
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')
            ->willReturn($response);

        return $handler;
    }

    private function createThrowingHandler(): RequestHandlerInterface
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')
            ->willThrowException(new \RuntimeException('Test exception'));

        return $handler;
    }
}
