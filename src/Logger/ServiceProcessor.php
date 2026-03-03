<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\Logger;

use Elastic\Types\Service;
use Monolog\LogRecord;

class ServiceProcessor
{
    protected Service $service;

    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $context = $record->context;
        if (!isset($context['service'])) {
            $context['service'] = $this->service;
        }

        return $record->with(context: $context);
    }
}
