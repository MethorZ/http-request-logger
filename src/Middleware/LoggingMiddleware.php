<?php

declare(strict_types=1);

namespace MethorZ\RequestLogger\Middleware;

use MethorZ\RequestLogger\Logger\PerformanceLogger;
use MethorZ\RequestLogger\Processor\RequestIdProcessor;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

use function microtime;

/**
 * PSR-15 middleware for automatic request logging with performance tracking
 *
 * Features:
 * - Assigns unique request ID to each request
 * - Logs request start/end with performance metrics
 * - Logs exceptions with request context
 * - Adds request ID to response header
 *
 * Usage:
 * ```php
 * $app->pipe(new LoggingMiddleware($logger));
 * ```
 */
final readonly class LoggingMiddleware implements MiddlewareInterface
{
    private PerformanceLogger $perfLogger;

    public function __construct(
        private LoggerInterface $logger,
        private ?RequestIdProcessor $requestIdProcessor = null,
        private bool $addRequestIdHeader = true,
    ) {
        $this->perfLogger = new PerformanceLogger($logger);
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        // Get or create request ID processor
        $processor = $this->requestIdProcessor ?? new RequestIdProcessor();
        $requestId = $processor->getRequestId();

        // Add processor to logger if using Monolog
        if (method_exists($this->logger, 'pushProcessor')) {
            $this->logger->pushProcessor($processor);
        }

        // Log request start
        $startTime = microtime(true);
        $this->logger->info('Request started', [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'request_id' => $requestId,
        ]);

        try {
            // Process request
            $response = $handler->handle($request);

            // Log successful completion
            $this->perfLogger->logRequest($startTime, [
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri(),
                'status' => $response->getStatusCode(),
                'request_id' => $requestId,
            ]);

            // Add request ID to response header
            if ($this->addRequestIdHeader) {
                $response = $response->withHeader('X-Request-ID', $requestId);
            }

            return $response;
        } catch (Throwable $e) {
            // Log error
            $this->logger->error('Request failed with exception', [
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri(),
                'exception' => $e->getMessage(),
                'exception_class' => $e::class,
                'request_id' => $requestId,
            ]);

            // Re-throw exception
            throw $e;
        } finally {
            // Remove processor if using Monolog
            if (method_exists($this->logger, 'popProcessor')) {
                $this->logger->popProcessor();
            }
        }
    }
}
