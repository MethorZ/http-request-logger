<?php

declare(strict_types=1);

namespace MethorZ\RequestLogger\Tests\Unit\Processor;

use MethorZ\RequestLogger\Processor\RequestIdProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;

final class RequestIdProcessorTest extends TestCase
{
    public function testAddsRequestIdToLogRecord(): void
    {
        $processor = new RequestIdProcessor('test-request-id');
        $record = new LogRecord(
            new \DateTimeImmutable(),
            'test',
            Level::Info,
            'Test message',
        );

        $processed = $processor($record);

        $this->assertArrayHasKey('request_id', $processed->extra);
        $this->assertSame('test-request-id', $processed->extra['request_id']);
    }

    public function testGeneratesUniqueRequestIdWhenNotProvided(): void
    {
        $processor1 = new RequestIdProcessor();
        $processor2 = new RequestIdProcessor();

        $this->assertNotSame($processor1->getRequestId(), $processor2->getRequestId());
    }

    public function testGeneratedRequestIdStartsWithPrefix(): void
    {
        $processor = new RequestIdProcessor();
        $requestId = $processor->getRequestId();

        $this->assertStringStartsWith('req_', $requestId);
    }

    public function testGetRequestIdReturnsConsistentId(): void
    {
        $processor = new RequestIdProcessor();
        $id1 = $processor->getRequestId();
        $id2 = $processor->getRequestId();

        $this->assertSame($id1, $id2);
    }

    public function testProcessorAddsConsistentRequestIdToMultipleRecords(): void
    {
        $processor = new RequestIdProcessor('consistent-id');

        $record1 = new LogRecord(
            new \DateTimeImmutable(),
            'test',
            Level::Info,
            'Message 1',
        );

        $record2 = new LogRecord(
            new \DateTimeImmutable(),
            'test',
            Level::Error,
            'Message 2',
        );

        $processed1 = $processor($record1);
        $processed2 = $processor($record2);

        $this->assertSame(
            $processed1->extra['request_id'],
            $processed2->extra['request_id'],
        );
    }
}
