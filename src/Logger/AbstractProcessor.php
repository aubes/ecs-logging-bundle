<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\Logger;

use Monolog\LogRecord;

abstract class AbstractProcessor
{
    protected string $fieldName;

    public function __construct(string $fieldName)
    {
        $this->fieldName = $fieldName;
    }

    abstract public function transformValue(mixed $value): mixed;

    abstract public function support(LogRecord $record): bool;

    abstract public function getTargetField(): string;

    public function __invoke(LogRecord $record): LogRecord
    {
        if (!$this->support($record)) {
            return $record;
        }

        $context = $record->context;
        $targetField = $this->getTargetField();
        $context[$targetField] = $this->transformValue($record->context[$this->fieldName]);

        if ($this->fieldName !== $targetField) {
            unset($context[$this->fieldName]);
        }

        return $record->with(context: $context);
    }
}
