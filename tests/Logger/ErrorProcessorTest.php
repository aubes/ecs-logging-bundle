<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\Tests\Logger;

use Aubes\EcsLoggingBundle\Logger\ErrorProcessor;
use Elastic\Types\Error;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;

class ErrorProcessorTest extends TestCase
{
    public function testWithErrorProcessor(): void
    {
        $processor = new ErrorProcessor('error');

        $record = new LogRecord(
            new \DateTimeImmutable(),
            'channel',
            Level::Info,
            'message',
            [
                'error' => new \Exception('message'),
            ]
        );

        $record = $processor($record);

        $this->assertArrayHasKey('error', $record->context);
        $this->assertInstanceOf(Error::class, $record->context['error']);
    }

    public function testWithErrorRenameProcessor(): void
    {
        $processor = new ErrorProcessor('error_custom');

        $record = new LogRecord(
            new \DateTimeImmutable(),
            'channel',
            Level::Info,
            'message',
            [
                'error_custom' => new \Exception('message'),
            ]
        );

        $record = $processor($record);

        $this->assertArrayHasKey('error', $record->context);
        $this->assertArrayNotHasKey('error_custom', $record->context);
        $this->assertInstanceOf(Error::class, $record->context['error']);
    }

    public function testWithoutErrorProcessor(): void
    {
        $processor = new ErrorProcessor('error');

        $record = new LogRecord(
            new \DateTimeImmutable(),
            'channel',
            Level::Info,
            'message',
            []
        );

        $record = $processor($record);

        $this->assertArrayHasKey('context', $record);
        $this->assertArrayNotHasKey('error', $record->context);
    }

    public function testWithNonThrowableErrorProcessor(): void
    {
        $processor = new ErrorProcessor('error');

        $record = new LogRecord(
            new \DateTimeImmutable(),
            'channel',
            Level::Info,
            'message',
            [
                'error' => 'Not Throwable',
            ]
        );

        $record = $processor($record);

        $this->assertSame('Not Throwable', $record->context['error']);
    }

    public function testWithAlreadyTransformedErrorProcessor(): void
    {
        $processor = new ErrorProcessor('error');

        $ecsError = new Error(new \Exception('already transformed'));

        $record = new LogRecord(
            new \DateTimeImmutable(),
            'channel',
            Level::Info,
            'message',
            [
                'error' => $ecsError,
            ]
        );

        $record = $processor($record);

        $this->assertArrayHasKey('error', $record->context);
        $this->assertSame($ecsError, $record->context['error']);
    }

    public function testMapExceptionKeyProcessesExceptionContext(): void
    {
        $processor = new ErrorProcessor('error', mapExceptionKey: true);

        $record = new LogRecord(
            new \DateTimeImmutable(),
            'channel',
            Level::Info,
            'message',
            [
                'exception' => new \Exception('from symfony'),
            ]
        );

        $record = $processor($record);

        $this->assertArrayHasKey('error', $record->context);
        $this->assertInstanceOf(Error::class, $record->context['error']);
        $this->assertArrayNotHasKey('exception', $record->context);
    }

    public function testMapExceptionKeyIsSkippedWhenTargetAlreadySet(): void
    {
        $processor = new ErrorProcessor('error', mapExceptionKey: true);

        $record = new LogRecord(
            new \DateTimeImmutable(),
            'channel',
            Level::Info,
            'message',
            [
                'error' => new \RuntimeException('primary'),
                'exception' => new \Exception('should be ignored'),
            ]
        );

        $record = $processor($record);

        $this->assertArrayHasKey('error', $record->context);
        $this->assertInstanceOf(Error::class, $record->context['error']);
        $this->assertArrayHasKey('exception', $record->context);
    }

    public function testMapExceptionKeyIsSkippedWhenNotThrowable(): void
    {
        $processor = new ErrorProcessor('error', mapExceptionKey: true);

        $record = new LogRecord(
            new \DateTimeImmutable(),
            'channel',
            Level::Info,
            'message',
            [
                'exception' => 'not a throwable',
            ]
        );

        $record = $processor($record);

        $this->assertArrayNotHasKey('error', $record->context);
        $this->assertArrayHasKey('exception', $record->context);
    }
}
