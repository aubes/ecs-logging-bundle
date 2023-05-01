<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\Logger;

abstract class AbstractProcessor
{
    protected string $fieldName;

    public function __construct(string $fieldName)
    {
        $this->fieldName = $fieldName;
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    abstract public function transformValue($value);

    abstract public function support(array $record): bool;

    abstract public function getTargetField(): string;

    public function __invoke(array $record): array
    {
        if (!$this->support($record)) {
            return $record;
        }

        $record['context'][$this->getTargetField()] = $this->transformValue($record['context'][$this->fieldName]);

        if ($this->fieldName !== $this->getTargetField()) {
            unset($record['context'][$this->fieldName]);
        }

        return $record;
    }
}
