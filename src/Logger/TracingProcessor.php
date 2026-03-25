<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\Logger;

use Elastic\Types\Tracing;
use Monolog\LogRecord;

final class TracingProcessor
{
    public function __construct(private readonly string $fieldName)
    {
    }

    public function __invoke(LogRecord $record): LogRecord
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
}
