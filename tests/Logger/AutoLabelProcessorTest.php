<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\Tests\Logger;

use Aubes\EcsLoggingBundle\Logger\AutoLabelProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class AutoLabelProcessorTest extends TestCase
{
    use ProphecyTrait;

    private function createRecord(array $context): LogRecord
    {
        return new LogRecord(
            new \DateTimeImmutable(),
            'channel',
            Level::Info,
            'message',
            $context
        );
    }

    public function testNonEcsFieldsAreMovedToLabels(): void
    {
        $processor = new AutoLabelProcessor([]);

        $record = $this->createRecord(['foo' => 'bar', 'baz' => 'qux']);
        $record = $processor($record);

        $this->assertArrayHasKey('labels', $record->context);
        $this->assertSame('bar', $record->context['labels']['foo']);
        $this->assertSame('qux', $record->context['labels']['baz']);
        $this->assertArrayNotHasKey('foo', $record->context);
        $this->assertArrayNotHasKey('baz', $record->context);
    }

    public function testEcsFieldsAreNotMoved(): void
    {
        $processor = new AutoLabelProcessor(['service', 'error']);

        $record = $this->createRecord(['service' => 'my-service', 'error' => 'some-error', 'custom' => 'value']);
        $record = $processor($record);

        $this->assertArrayHasKey('service', $record->context);
        $this->assertArrayHasKey('error', $record->context);
        $this->assertArrayHasKey('labels', $record->context);
        $this->assertSame('value', $record->context['labels']['custom']);
        $this->assertArrayNotHasKey('custom', $record->context);
    }

    public function testEmptyContextIsUnchanged(): void
    {
        $processor = new AutoLabelProcessor(AutoLabelProcessor::FIELDS_ALL);

        $record = $this->createRecord([]);
        $record = $processor($record);

        $this->assertSame([], $record->context);
    }

    public function testAllContextFieldsAreEcsFields(): void
    {
        $processor = new AutoLabelProcessor(['foo', 'bar']);

        $record = $this->createRecord(['foo' => 1, 'bar' => 2]);
        $record = $processor($record);

        $this->assertArrayHasKey('foo', $record->context);
        $this->assertArrayHasKey('bar', $record->context);
        $this->assertArrayNotHasKey('labels', $record->context);
    }

    public function testFieldsMinimalConstant(): void
    {
        $processor = new AutoLabelProcessor(AutoLabelProcessor::FIELDS_MINIMAL);

        $record = $this->createRecord([
            'message' => 'text',
            'service' => 'svc',
            'custom_field' => 'moved',
        ]);
        $record = $processor($record);

        $this->assertArrayHasKey('message', $record->context);
        $this->assertArrayHasKey('service', $record->context);
        $this->assertArrayHasKey('labels', $record->context);
        $this->assertSame('moved', $record->context['labels']['custom_field']);
        $this->assertArrayNotHasKey('custom_field', $record->context);
    }

    public function testFieldsBundleConstant(): void
    {
        $processor = new AutoLabelProcessor(AutoLabelProcessor::FIELDS_BUNDLE);

        $record = $this->createRecord([
            'error' => 'some-error',
            'user' => 'some-user',
            'unexpected' => 'should-be-labeled',
        ]);
        $record = $processor($record);

        $this->assertArrayHasKey('error', $record->context);
        $this->assertArrayHasKey('user', $record->context);
        $this->assertSame('should-be-labeled', $record->context['labels']['unexpected']);
        $this->assertArrayNotHasKey('unexpected', $record->context);
    }

    public function testInvalidLabelsThrowsException(): void
    {
        // 'labels' is declared as an ECS field (stays in context), but holds a non-array value
        $processor = new AutoLabelProcessor(['labels']);

        $record = $this->createRecord(['labels' => 'not-an-array', 'foo' => 'bar']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "labels" context field must be an array, "string" given.');

        $processor($record);
    }

    public function testExistingLabelsAreMerged(): void
    {
        $processor = new AutoLabelProcessor(['labels']);

        $record = $this->createRecord([
            'labels' => ['existing' => 'value'],
            'new_field' => 'new_value',
        ]);
        $record = $processor($record);

        $this->assertArrayHasKey('labels', $record->context);
        $this->assertSame('value', $record->context['labels']['existing']);
        $this->assertSame('new_value', $record->context['labels']['new_field']);
    }
}
