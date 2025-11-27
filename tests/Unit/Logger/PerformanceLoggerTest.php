<?php

declare(strict_types=1);

namespace MethorZ\RequestLogger\Tests\Unit\Logger;

use MethorZ\RequestLogger\Logger\PerformanceLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final class PerformanceLoggerTest extends TestCase
{
    public function testLogOperationLogsPerformanceMetrics(): void
    {
        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockLogger->expects($this->once())
            ->method('log')
            ->with(
                LogLevel::INFO,
                'Performance: test-operation',
                $this->callback(function (array $context): bool {
                    $this->assertArrayHasKey('operation', $context);
                    $this->assertArrayHasKey('duration_ms', $context);
                    $this->assertArrayHasKey('memory_peak_mb', $context);
                    $this->assertSame('test-operation', $context['operation']);
                    $this->assertIsFloat($context['duration_ms']);
                    $this->assertGreaterThan(0, $context['duration_ms']);

                    return true;
                }),
            );

        $perfLogger = new PerformanceLogger($mockLogger);
        $startTime = microtime(true);
        usleep(1000); // 1ms
        $perfLogger->logOperation('test-operation', $startTime);
    }

    public function testLogOperationIncludesAdditionalContext(): void
    {
        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockLogger->expects($this->once())
            ->method('log')
            ->with(
                LogLevel::INFO,
                'Performance: test-op',
                $this->callback(function (array $context): bool {
                    $this->assertArrayHasKey('custom_key', $context);
                    $this->assertSame('custom_value', $context['custom_key']);

                    return true;
                }),
            );

        $perfLogger = new PerformanceLogger($mockLogger);
        $startTime = microtime(true);
        $perfLogger->logOperation('test-op', $startTime, ['custom_key' => 'custom_value']);
    }

    public function testLogRequestLogsRequestMetrics(): void
    {
        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockLogger->expects($this->once())
            ->method('info')
            ->with(
                'Request completed',
                $this->callback(function (array $context): bool {
                    $this->assertArrayHasKey('duration_ms', $context);
                    $this->assertArrayHasKey('memory_peak_mb', $context);
                    $this->assertIsFloat($context['duration_ms']);
                    $this->assertGreaterThan(0, $context['duration_ms']);

                    return true;
                }),
            );

        $perfLogger = new PerformanceLogger($mockLogger);
        $startTime = microtime(true);
        usleep(1000); // 1ms
        $perfLogger->logRequest($startTime);
    }

    public function testMeasureExecutesCallbackAndLogsPerformance(): void
    {
        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockLogger->expects($this->once())
            ->method('log');

        $perfLogger = new PerformanceLogger($mockLogger);

        $result = $perfLogger->measure('test-measure', function (): string {
            usleep(1000);

            return 'result';
        });

        $this->assertSame('result', $result);
    }

    public function testMeasureLogsEvenIfCallbackThrows(): void
    {
        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockLogger->expects($this->once())
            ->method('log');

        $perfLogger = new PerformanceLogger($mockLogger);

        $this->expectException(\RuntimeException::class);

        $perfLogger->measure('failing-operation', function (): void {
            throw new \RuntimeException('Test exception');
        });
    }
}
