<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\Logger;

use Elastic\Types\Service;
use Monolog\LogRecord;

final class ServiceProcessor
{
    public function __construct(private readonly Service $service)
    {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        if (isset($record->context['service'])) {
            return $record;
        }

        $context = $record->context;
        $context['service'] = $this->service;

        return $record->with(context: $context);
    }
}
