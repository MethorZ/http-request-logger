<?php

declare(strict_types=1);

namespace MethorZ\RequestLogger\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

use function memory_get_peak_usage;
use function microtime;
use function round;

/**
 * Logs performance metrics for requests and operations
 *
 * Immutable logger for performance tracking. Uses a functional approach
 * with the measure() method instead of mutable start()/end() pattern.
 *
 * Usage:
 * ```php
 * $perfLogger = new PerformanceLogger($logger);
 *
 * // Preferred: Functional approach (immutable)
 * $result = $perfLogger->measure('database-query', function() {
 *     return $db->query('SELECT ...');
 * }, ['query' => 'SELECT ...']);
 *
 * // Alternative: Manual timing
 * $startTime = microtime(true);
 * // ... perform operation ...
 * $perfLogger->logOperation('database-query', $startTime, ['query' => 'SELECT ...']);
 * ```
 */
final readonly class PerformanceLogger
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Log performance metrics for the entire request
     *
     * @param float $startTime Request start time (from microtime(true))
     * @param array<string, mixed> $context Additional context
     */
    public function logRequest(float $startTime, array $context = []): void
    {
        $duration = round((microtime(true) - $startTime) * 1000, 2);

        $this->logger->info('Request completed', [
            'duration_ms' => $duration,
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            ...$context,
        ]);
    }

    /**
     * Log performance metrics for a completed operation
     *
     * @param string $operation Operation name
     * @param float $startTime Operation start time (from microtime(true))
     * @param array<string, mixed> $context Additional context to log
     * @param string $level Log level (default: info)
     */
    public function logOperation(
        string $operation,
        float $startTime,
        array $context = [],
        string $level = LogLevel::INFO,
    ): void {
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2); // Convert to milliseconds

        $perfContext = [
            'operation' => $operation,
            'duration_ms' => $duration,
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        ];

        $this->logger->log($level, "Performance: {$operation}", array_merge($perfContext, $context));
    }

    /**
     * Measure and log a callable's performance (functional approach)
     *
     * This is the preferred method for performance logging as it's
     * stateless and provides automatic cleanup.
     *
     * @template T
     * @param string $operation Operation name
     * @param callable(): T $callback Operation to measure
     * @param array<string, mixed> $context Additional context
     * @param string $level Log level (default: info)
     * @return T Result from the callback
     */
    public function measure(
        string $operation,
        callable $callback,
        array $context = [],
        string $level = LogLevel::INFO,
    ): mixed {
        $startTime = microtime(true);

        try {
            return $callback();
        } finally {
            $this->logOperation($operation, $startTime, $context, $level);
        }
    }
}
