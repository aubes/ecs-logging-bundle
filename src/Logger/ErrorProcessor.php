<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\Logger;

use Elastic\Types\Error;

class ErrorProcessor extends AbstractProcessor
{
    public function getTargetField(): string
    {
        return 'error';
    }

    public function support(array $record): bool
    {
        return isset($record['context'][$this->fieldName]) && !$record['context'][$this->fieldName] instanceof Error;
    }

    public function transformValue($value)
    {
        if (!$value instanceof \Throwable) {
            throw new \InvalidArgumentException($this->fieldName . ' must be an instance of Throwable');
        }

        return new Error($value);
    }
}
