<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\Logger;

use Elastic\Types\Tracing;
use Monolog\LogRecord;

final class TracingProcessor
{
    public const MODE_DEFAULT = 'default';
    public const MODE_OPENTELEMETRY = 'opentelemetry';

    public function __construct(
        private readonly string $fieldName = 'tracing',
        private readonly string $mode = self::MODE_DEFAULT,
    ) {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        return match ($this->mode) {
            self::MODE_OPENTELEMETRY => $this->processOpenTelemetry($record),
            default => $this->processDefault($record),
        };
    }

    private function processDefault(LogRecord $record): LogRecord
    {
        $value = $record->context[$this->fieldName] ?? null;

        if (!\is_array($value) || !isset($value['trace_id'])) {
            return $record;
        }

        $context = $record->context;
        $context['tracing'] = new Tracing((string) $value['trace_id'], $value['transaction_id'] ?? null);

        if ($this->fieldName !== 'tracing') {
            unset($context[$this->fieldName]);
        }

        if (isset($value['span_id']) && !isset($context['span'])) {
            $context['span'] = ['id' => (string) $value['span_id']];
        }

        return $record->with(context: $context);
    }

    private function processOpenTelemetry(LogRecord $record): LogRecord
    {
        $traceId = $record->context['trace_id'] ?? null;

        if (!\is_string($traceId) || $traceId === '') {
            return $record;
        }

        $context = $record->context;

        if (!isset($context['tracing'])) {
            $spanId = $context['span_id'] ?? null;
            $context['tracing'] = new Tracing($traceId, \is_string($spanId) ? $spanId : null);
        }

        if (!isset($context['span']) && isset($context['span_id']) && \is_string($context['span_id'])) {
            $context['span'] = ['id' => $context['span_id']];
        }

        unset($context['trace_id'], $context['span_id'], $context['trace_flags']);

        return $record->with(context: $context);
    }
}
