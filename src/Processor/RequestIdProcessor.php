<?php

declare(strict_types=1);

namespace MethorZ\RequestLogger\Processor;

use Monolog\LogRecord;

use function uniqid;

/**
 * Adds a unique request ID to all log records
 *
 * The request ID persists for the lifetime of the current request,
 * allowing you to correlate all logs from a single request.
 *
 * Monolog is optional, so this class must stay loadable without it: it is
 * passed to Logger::pushProcessor() as a callable instead of implementing
 * Monolog\Processor\ProcessorInterface.
 *
 * Usage with Monolog:
 * ```php
 * $logger->pushProcessor(new RequestIdProcessor());
 * ```
 */
final class RequestIdProcessor
{
    private string $requestId;

    /**
     * @param string|null $requestId Optional custom request ID (auto-generated if null)
     */
    public function __construct(?string $requestId = null)
    {
        $this->requestId = $requestId ?? $this->generateRequestId();
    }

    /**
     * @param LogRecord $record
     * @return LogRecord
     */
    public function __invoke(LogRecord $record): LogRecord
    {
        $record->extra['request_id'] = $this->requestId;

        return $record;
    }

    /**
     * Get the current request ID
     */
    public function getRequestId(): string
    {
        return $this->requestId;
    }

    /**
     * Generate a unique request ID
     */
    private function generateRequestId(): string
    {
        return uniqid('req_', true);
    }
}
