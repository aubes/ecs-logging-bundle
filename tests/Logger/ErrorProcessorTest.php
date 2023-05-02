<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\Tests\Logger;

use Aubes\EcsLoggingBundle\Logger\ErrorProcessor;
use Elastic\Types\Error;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class ErrorProcessorTest extends TestCase
{
    use ProphecyTrait;

    public function testWithErrorProcessor()
    {
        $processor = new ErrorProcessor('error');

        $record = [
            'context' => [
                'error' => new \Exception('message'),
            ],
        ];

        $record = $processor($record);

        $this->assertArrayHasKey('context', $record);
        $this->assertArrayHasKey('error', $record['context']);
        $this->assertInstanceOf(Error::class, $record['context']['error']);
    }

    public function testWithErrorRenameProcessor()
    {
        $processor = new ErrorProcessor('error_custom');

        $record = [
            'context' => [
                'error_custom' => new \Exception('message'),
            ],
        ];

        $record = $processor($record);

        $this->assertArrayHasKey('context', $record);
        $this->assertArrayHasKey('error', $record['context']);
        $this->assertArrayNotHasKey('error_custom', $record['context']);
        $this->assertInstanceOf(Error::class, $record['context']['error']);
    }

    public function testWithoutErrorProcessor()
    {
        $processor = new ErrorProcessor('error');

        $record = [
            'context' => [],
        ];

        $record = $processor($record);

        $this->assertArrayHasKey('context', $record);
        $this->assertArrayNotHasKey('error', $record['context']);
    }

    public function testWithNonThrowableErrorProcessor()
    {
        $processor = new ErrorProcessor('error');

        $record = [
            'context' => [
                'error' => 'Not Throwable',
            ],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('error must be an instance of Throwable');

        $record = $processor($record);

        $this->assertArrayHasKey('context', $record);
        $this->assertArrayNotHasKey('error', $record['context']);
    }
}
