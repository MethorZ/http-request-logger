<?php

declare(strict_types=1);

namespace MethorZ\RequestLogger\Integration\Mezzio;

use MethorZ\RequestLogger\Middleware\LoggingMiddleware;

/**
 * Mezzio configuration provider for the http-request-logger package
 *
 * Registers the LoggingMiddleware with automatic configuration.
 *
 * NOTE: This is a Mezzio-specific integration. For other frameworks,
 * you can manually instantiate LoggingMiddleware with your desired configuration.
 *
 * Usage in config/config.php:
 * ```php
 * $aggregator = new ConfigAggregator([
 *     MethorZ\RequestLogger\Integration\Mezzio\ConfigProvider::class,
 *     // ... other providers
 * ]);
 * ```
 */
final class ConfigProvider
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function getDependencies(): array
    {
        return [
            'factories' => [
                LoggingMiddleware::class => LoggingMiddlewareFactory::class,
            ],
        ];
    }
}
