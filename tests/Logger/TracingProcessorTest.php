<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\Tests\Logger;

use Aubes\EcsLoggingBundle\Logger\TracingProcessor;
use Elastic\Types\Tracing;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class TracingProcessorTest extends TestCase
{
    use ProphecyTrait;

    public function testWithTracingTransformer()
    {
        $processor = new TracingProcessor('tracing');

        $record = [
            'context' => [
                'tracing' => [
                    'trace_id' => '123',
                    'transaction_id' => '123',
                ],
            ],
        ];

        $record = $processor($record);

        $this->assertArrayHasKey('context', $record);
        $this->assertArrayHasKey('tracing', $record['context']);
        $this->assertInstanceOf(Tracing::class, $record['context']['tracing']);
    }

    public function testWithTracingRenameTransformer()
    {
        $processor = new TracingProcessor('trace_custom');

        $record = [
            'context' => [
                'trace_custom' => [
                    'trace_id' => '123',
                    'transaction_id' => '123',
                ],
            ],
        ];

        $record = $processor($record);

        $this->assertArrayHasKey('context', $record);
        $this->assertArrayHasKey('tracing', $record['context']);
        $this->assertArrayNotHasKey('trace_custom', $record['context']);
        $this->assertInstanceOf(Tracing::class, $record['context']['tracing']);
    }

    public function testWithoutTracingTransformer()
    {
        $processor = new TracingProcessor('tracing');

        $record = [
            'context' => [],
        ];

        $record = $processor($record);

        $this->assertArrayHasKey('context', $record);
        $this->assertArrayNotHasKey('tracing', $record['context']);
    }

    public function testWithTracingWithoutTraceIdTransformer()
    {
        $processor = new TracingProcessor('tracing');

        $record = [
            'context' => [
                'tracing' => [],
            ],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('trace_id is required when tracing is provided');

        $record = $processor($record);

        $this->assertArrayHasKey('context', $record);
        $this->assertArrayNotHasKey('tracing', $record['context']);
    }
}
