<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\Logger;

use Aubes\EcsLoggingBundle\Security\EcsUserProviderInterface;
use Monolog\LogRecord;

final class UserProcessor
{
    protected EcsUserProviderInterface $provider;
    protected ?string $domain;

    public function __construct(EcsUserProviderInterface $provider, ?string $domain = null)
    {
        $this->provider = $provider;
        $this->domain = $domain;
    }

    public function support(LogRecord $record): bool
    {
        return !isset($record->context['user']);
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        if (!$this->support($record)) {
            return $record;
        }

        $ecsUser = $this->provider->getUser();

        if ($ecsUser === null) {
            return $record;
        }

        $domain = $this->provider->getDomain() ?? $this->domain;
        if ($domain !== null) {
            $ecsUser->setDomain($domain);
        }

        $context = $record->context;
        $context['user'] = $ecsUser;

        return $record->with(context: $context);
    }
}
