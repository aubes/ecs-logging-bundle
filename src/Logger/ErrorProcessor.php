<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\Logger;

use Elastic\Types\Error;
use Monolog\LogRecord;

final class ErrorProcessor extends AbstractProcessor
{
    public function getTargetField(): string
    {
        return 'error';
    }

    public function support(LogRecord $record): bool
    {
        return isset($record->context[$this->fieldName]) && !$record->context[$this->fieldName] instanceof Error;
    }

    public function transformValue(mixed $value): Error
    {
        if (!$value instanceof \Throwable) {
            throw new \InvalidArgumentException($this->fieldName . ' must be an instance of Throwable');
        }

        return new Error($value);
    }
}
