<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\Logger;

use Elastic\Types\Tracing;

class TracingProcessor extends AbstractProcessor
{
    public function getTargetField(): string
    {
        return 'tracing';
    }

    public function support(array $record): bool
    {
        return isset($record['context'][$this->fieldName]) && !$record['context'][$this->fieldName] instanceof Tracing;
    }

    public function transformValue($value)
    {
        if (!isset($value['trace_id'])) {
            throw new \InvalidArgumentException('trace_id is required when ' . $this->fieldName . ' is provided');
        }

        return new Tracing((string) $value['trace_id'], $value['transaction_id'] ?? null);
    }
}
