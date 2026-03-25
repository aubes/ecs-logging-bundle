<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\Tests\Logger;

use Aubes\EcsLoggingBundle\Logger\AutoLabelProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;

class AutoLabelProcessorTest extends TestCase
{
    /** @param array<string, mixed> $context */
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

    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $extra
     */
    private function createRecordWithExtra(array $context, array $extra): LogRecord
    {
        return new LogRecord(
            new \DateTimeImmutable(),
            'channel',
            Level::Info,
            'message',
            $context,
            $extra,
        );
    }

    // --- Default behavior: drop ---

    public function testNonEcsFieldsAreDroppedByDefault(): void
    {
        $processor = new AutoLabelProcessor([]);

        $record = $this->createRecord(['foo' => 'bar', 'baz' => 'qux']);
        $record = $processor($record);

        $this->assertArrayNotHasKey('foo', $record->context);
        $this->assertArrayNotHasKey('baz', $record->context);
        $this->assertArrayNotHasKey('labels', $record->context);
    }

    public function testEcsFieldsAreKeptAndNonEcsFieldsAreDropped(): void
    {
        $processor = new AutoLabelProcessor(['service', 'error']);

        $record = $this->createRecord(['service' => 'my-service', 'error' => 'some-error', 'custom' => 'value']);
        $record = $processor($record);

        $this->assertArrayHasKey('service', $record->context);
        $this->assertArrayHasKey('error', $record->context);
        $this->assertArrayNotHasKey('custom', $record->context);
        $this->assertArrayNotHasKey('labels', $record->context);
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

    // --- Move-to-labels behavior ---

    public function testNonEcsFieldsAreMovedToLabelsWhenEnabled(): void
    {
        $processor = new AutoLabelProcessor([], moveToLabels: true);

        $record = $this->createRecord(['foo' => 'bar', 'baz' => 'qux']);
        $record = $processor($record);

        $this->assertArrayHasKey('labels', $record->context);
        $this->assertSame('bar', $record->context['labels']['foo']);
        $this->assertSame('qux', $record->context['labels']['baz']);
        $this->assertArrayNotHasKey('foo', $record->context);
        $this->assertArrayNotHasKey('baz', $record->context);
    }

    public function testFieldsBundleConstant(): void
    {
        $processor = new AutoLabelProcessor(AutoLabelProcessor::FIELDS_BUNDLE, moveToLabels: true);

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

    public function testInvalidLabelsIsOverwritten(): void
    {
        // 'labels' holds a non-array value (already invalid ECS) — must be silently overwritten
        $processor = new AutoLabelProcessor(['labels'], moveToLabels: true);

        $record = $this->createRecord(['labels' => 'not-an-array', 'foo' => 'bar']);
        $record = $processor($record);

        $this->assertIsArray($record->context['labels']);
        $this->assertSame('bar', $record->context['labels']['foo']);
    }

    public function testExistingLabelsAreMerged(): void
    {
        $processor = new AutoLabelProcessor(['labels'], moveToLabels: true);

        $record = $this->createRecord([
            'labels' => ['existing' => 'value'],
            'new_field' => 'new_value',
        ]);
        $record = $processor($record);

        $this->assertArrayHasKey('labels', $record->context);
        $this->assertSame('value', $record->context['labels']['existing']);
        $this->assertSame('new_value', $record->context['labels']['new_field']);
    }

    public function testExistingLabelsWinOnKeyCollision(): void
    {
        $processor = new AutoLabelProcessor(['labels'], moveToLabels: true);

        $record = $this->createRecord([
            'labels' => ['foo' => 'explicit'],
            'foo' => 'auto-moved',
        ]);
        $record = $processor($record);

        // The explicitly set label must not be overwritten by the auto-moved field.
        $this->assertSame('explicit', $record->context['labels']['foo']);
    }

    // --- include_extra ---

    public function testIncludeExtraIsDisabledByDefault(): void
    {
        $processor = new AutoLabelProcessor([]);

        $record = $this->createRecordWithExtra([], ['process_id' => 42]);
        $record = $processor($record);

        $this->assertArrayNotHasKey('labels', $record->context);
        $this->assertArrayHasKey('process_id', $record->extra);
    }

    public function testIncludeExtraDropsNonEcsExtraKeysByDefault(): void
    {
        $processor = new AutoLabelProcessor([], includeExtra: true);

        $record = $this->createRecordWithExtra([], ['process_id' => 42, 'memory_usage' => '8 MB']);
        $record = $processor($record);

        $this->assertArrayNotHasKey('labels', $record->context);
        $this->assertArrayNotHasKey('process_id', $record->extra);
        $this->assertArrayNotHasKey('memory_usage', $record->extra);
    }

    public function testIncludeExtraMovesNonEcsExtraKeysToLabels(): void
    {
        $processor = new AutoLabelProcessor([], moveToLabels: true, includeExtra: true);

        $record = $this->createRecordWithExtra([], ['process_id' => 42, 'memory_usage' => '8 MB']);
        $record = $processor($record);

        $this->assertSame(42, $record->context['labels']['process_id']);
        $this->assertSame('8 MB', $record->context['labels']['memory_usage']);
        $this->assertArrayNotHasKey('process_id', $record->extra);
        $this->assertArrayNotHasKey('memory_usage', $record->extra);
    }

    public function testIncludeExtraPreservesEcsExtraKeys(): void
    {
        $processor = new AutoLabelProcessor(['process'], moveToLabels: true, includeExtra: true);

        $record = $this->createRecordWithExtra([], ['process' => ['pid' => 42], 'uid' => 'abc']);
        $record = $processor($record);

        $this->assertArrayHasKey('process', $record->extra);
        $this->assertSame('abc', $record->context['labels']['uid']);
        $this->assertArrayNotHasKey('uid', $record->extra);
    }

    public function testIncludeExtraMergesWithExistingContextLabels(): void
    {
        $processor = new AutoLabelProcessor(['labels'], moveToLabels: true, includeExtra: true);

        $record = $this->createRecordWithExtra(
            ['labels' => ['existing' => 'value']],
            ['extra_key' => 'extra_value'],
        );
        $record = $processor($record);

        $this->assertSame('value', $record->context['labels']['existing']);
        $this->assertSame('extra_value', $record->context['labels']['extra_key']);
    }

    public function testIncludeExtraWithEmptyExtraIsUnchanged(): void
    {
        $processor = new AutoLabelProcessor([], includeExtra: true);

        $record = $this->createRecordWithExtra([], []);
        $record = $processor($record);

        $this->assertSame([], $record->context);
        $this->assertSame([], $record->extra);
    }

    // --- non_scalar_strategy ---

    public function testSkipStrategyIsDefault(): void
    {
        $processor = new AutoLabelProcessor([]);

        $record = $this->createRecord(['foo' => ['nested' => 'array']]);
        $record = $processor($record);

        $this->assertArrayNotHasKey('foo', $record->context);
        $this->assertArrayNotHasKey('labels', $record->context);
    }

    public function testSkipStrategyRemovesNonScalarContextFields(): void
    {
        $processor = new AutoLabelProcessor([], moveToLabels: true, nonScalarStrategy: AutoLabelProcessor::STRATEGY_SKIP);

        $record = $this->createRecord(['obj' => new \stdClass(), 'scalar' => 'ok']);
        $record = $processor($record);

        // non-scalar field is removed from context and not added to labels
        $this->assertArrayNotHasKey('obj', $record->context);
        $this->assertArrayNotHasKey('obj', $record->context['labels'] ?? []);
        // scalar non-ECS field is moved to labels
        $this->assertSame('ok', $record->context['labels']['scalar']);
    }

    public function testJsonStrategyConvertsNonScalarToString(): void
    {
        $processor = new AutoLabelProcessor([], moveToLabels: true, nonScalarStrategy: AutoLabelProcessor::STRATEGY_JSON);

        $record = $this->createRecord(['meta' => ['key' => 'value'], 'scalar' => 'ok']);
        $record = $processor($record);

        $this->assertArrayNotHasKey('meta', $record->context);
        $this->assertSame('{"key":"value"}', $record->context['labels']['meta']);
        $this->assertSame('ok', $record->context['labels']['scalar']);
    }

    public function testJsonStrategyFallsBackToSkipOnEncodingFailure(): void
    {
        $processor = new AutoLabelProcessor([], moveToLabels: true, nonScalarStrategy: AutoLabelProcessor::STRATEGY_JSON);

        // Object with circular reference cannot be json_encoded
        $obj = new \stdClass();
        $obj->self = $obj;
        $record = $this->createRecord(['bad' => $obj]);
        $record = $processor($record);

        $this->assertArrayNotHasKey('bad', $record->context);
        $this->assertArrayNotHasKey('labels', $record->context);
    }

    public function testSkipStrategyRemovesNonScalarExtraFields(): void
    {
        $processor = new AutoLabelProcessor([], includeExtra: true, nonScalarStrategy: AutoLabelProcessor::STRATEGY_SKIP);

        $record = $this->createRecordWithExtra([], ['obj' => new \stdClass()]);
        $record = $processor($record);

        $this->assertArrayNotHasKey('obj', $record->extra);
        $this->assertArrayNotHasKey('labels', $record->context);
    }

    public function testJsonStrategyConvertsNonScalarExtraToString(): void
    {
        $processor = new AutoLabelProcessor([], moveToLabels: true, includeExtra: true, nonScalarStrategy: AutoLabelProcessor::STRATEGY_JSON);

        $record = $this->createRecordWithExtra([], ['meta' => ['k' => 'v']]);
        $record = $processor($record);

        $this->assertArrayNotHasKey('meta', $record->extra);
        $this->assertSame('{"k":"v"}', $record->context['labels']['meta']);
    }
}
