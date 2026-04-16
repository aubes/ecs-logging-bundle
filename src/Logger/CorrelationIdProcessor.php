<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\Logger;

use Monolog\LogRecord;

final class CorrelationIdProcessor
{
    public const TARGET_LABELS = 'labels';
    public const TARGET_TRACE = 'trace';

    /**
     * @param non-empty-string $fieldName Key to read from Monolog extra (e.g. "correlation_id").
     */
    public function __construct(
        private readonly string $fieldName = 'correlation_id',
        private readonly string $target = self::TARGET_LABELS,
    ) {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $correlationId = $record->extra[$this->fieldName] ?? null;

        if (!\is_string($correlationId) || $correlationId === '') {
            return $record;
        }

        $context = $record->context;
        $extra = $record->extra;

        if ($this->target === self::TARGET_TRACE) {
            if (!isset($context['trace']['id'])) {
                $context['trace'] = \array_merge($context['trace'] ?? [], ['id' => $correlationId]);
            }
        } else {
            if (!isset($context['labels']['correlation_id'])) {
                $labels = $context['labels'] ?? [];

                if (!\is_array($labels)) {
                    $labels = [];
                }

                $labels['correlation_id'] = $correlationId;
                $context['labels'] = $labels;
            }
        }

        unset($extra[$this->fieldName]);

        return $record->with(context: $context, extra: $extra);
    }
}
