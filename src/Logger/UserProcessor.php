<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\Logger;

use Aubes\EcsLoggingBundle\Security\EcsUserProviderInterface;

class UserProcessor
{
    protected EcsUserProviderInterface $provider;
    protected ?string $domain;

    public function __construct(EcsUserProviderInterface $provider, string $domain = null)
    {
        $this->provider = $provider;
        $this->domain = $domain;
    }

    public function support(array $record): bool
    {
        return ($record['context']['user'] ?? null) === null;
    }

    public function __invoke(array $record): array
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

        $record['context']['user'] = $ecsUser;

        return $record;
    }
}
