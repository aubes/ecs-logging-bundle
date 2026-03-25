<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\Logger;

use Monolog\LogRecord;

abstract class AbstractProcessor
{
    public function __construct(
        protected readonly string $fieldName,
        private readonly string $targetField,
    ) {
    }

    abstract protected function transformValue(mixed $value): mixed;

    abstract protected function support(LogRecord $record): bool;

    final protected function getTargetField(): string
    {
        return $this->targetField;
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        if (!$this->support($record)) {
            return $record;
        }

        $context = $record->context;
        $targetField = $this->targetField;
        $context[$targetField] = $this->transformValue($record->context[$this->fieldName]);

        if ($this->fieldName !== $targetField) {
            unset($context[$this->fieldName]);
        }

        return $record->with(context: $context);
    }
}
