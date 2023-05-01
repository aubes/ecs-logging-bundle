<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\Logger;

use Elastic\Types\Service;

class ServiceProcessor
{
    protected Service $service;

    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    public function __invoke(array $record): array
    {
        if (!isset($record['context']['service'])) {
            $record['context']['service'] = $this->service;
        }

        return $record;
    }
}
