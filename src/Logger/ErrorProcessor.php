<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\Logger;

use Elastic\Types\Error;
use Monolog\LogRecord;

final class ErrorProcessor extends AbstractProcessor
{
    public function __construct(string $fieldName, private readonly bool $mapExceptionKey = false)
    {
        parent::__construct($fieldName, 'error');
    }

    protected function support(LogRecord $record): bool
    {
        return isset($record->context[$this->fieldName]) && $record->context[$this->fieldName] instanceof \Throwable;
    }

    protected function transformValue(mixed $value): Error
    {
        return new Error($value);
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $record = parent::__invoke($record);

        if (!$this->mapExceptionKey || isset($record->context[$this->getTargetField()])) {
            return $record;
        }

        if (!isset($record->context['exception']) || !$record->context['exception'] instanceof \Throwable) {
            return $record;
        }

        $context = $record->context;
        $context[$this->getTargetField()] = new Error($context['exception']);
        unset($context['exception']);

        return $record->with(context: $context);
    }
}
