<?php

declare(strict_types=1);

namespace MethorZ\RequestLogger\Integration\Mezzio;

use MethorZ\RequestLogger\Middleware\LoggingMiddleware;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Mezzio/Laminas ServiceManager factory for LoggingMiddleware
 *
 * Reads configuration from 'request_logging' key in container config.
 * Provides sensible defaults for zero-config usage.
 *
 * NOTE: This is a Mezzio-specific integration. For other frameworks,
 * you can manually instantiate LoggingMiddleware with your desired configuration.
 *
 * Configuration example (config/autoload/request-logging.global.php):
 * ```php
 * return [
 *     'request_logging' => [
 *         'add_request_id_header' => true,
 *         'log_request_body' => false,
 *         'log_response_body' => false,
 *     ],
 * ];
 * ```
 */
final class LoggingMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): LoggingMiddleware
    {
        // Try to get logger, fallback to NullLogger if somehow not available
        $logger = $container->has(LoggerInterface::class)
            ? $container->get(LoggerInterface::class)
            : new NullLogger();

        // Read configuration from container (if available)
        $config = $container->has('config') ? $container->get('config') : [];

        /** @var array{add_request_id_header?: bool, log_request_body?: bool, log_response_body?: bool} $loggingConfig */
        $loggingConfig = $config['request_logging'] ?? [];

        // Extract configuration with sensible defaults
        $addRequestIdHeader = $loggingConfig['add_request_id_header'] ?? true;

        return new LoggingMiddleware(
            logger: $logger,
            addRequestIdHeader: $addRequestIdHeader,
        );
    }
}
