<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\Tests\Logger;

use Aubes\EcsLoggingBundle\Logger\TracingProcessor;
use Elastic\Types\Tracing;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class TracingProcessorTest extends TestCase
{
    use ProphecyTrait;

    public function testWithTracingProcessor()
    {
        $processor = new TracingProcessor('tracing');

        $record = new LogRecord(
            new \DateTimeImmutable(),
            'channel',
            Level::Info,
            'message',
            [
                'tracing' => [
                    'trace_id' => '123',
                    'transaction_id' => '123',
                ],
            ]
        );

        $record = $processor($record);

        $this->assertArrayHasKey('tracing', $record->context);
        $this->assertInstanceOf(Tracing::class, $record->context['tracing']);
    }

    public function testWithTracingRenameProcessor()
    {
        $processor = new TracingProcessor('trace_custom');

        $record = new LogRecord(
            new \DateTimeImmutable(),
            'channel',
            Level::Info,
            'message',
            [
                'trace_custom' => [
                    'trace_id' => '123',
                    'transaction_id' => '123',
                ],
            ]
        );

        $record = $processor($record);

        $this->assertArrayHasKey('tracing', $record->context);
        $this->assertArrayNotHasKey('trace_custom', $record->context);
        $this->assertInstanceOf(Tracing::class, $record->context['tracing']);
    }

    public function testWithoutTracingProcessor()
    {
        $processor = new TracingProcessor('tracing');

        $record = new LogRecord(
            new \DateTimeImmutable(),
            'channel',
            Level::Info,
            'message',
            []
        );

        $record = $processor($record);

        $this->assertArrayNotHasKey('tracing', $record->context);
    }

    public function testWithTracingWithoutTraceIdProcessor()
    {
        $processor = new TracingProcessor('tracing');

        $record = new LogRecord(
            new \DateTimeImmutable(),
            'channel',
            Level::Info,
            'message',
            [
                'tracing' => [],
            ]
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('trace_id is required when tracing is provided');

        $record = $processor($record);

        $this->assertArrayNotHasKey('tracing', $record->context);
    }
}
