<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\Logger;

use Aubes\EcsLoggingBundle\Security\EcsUserProviderInterface;
use Elastic\Types\User;
use Monolog\LogRecord;
use Symfony\Contracts\Service\ResetInterface;

final class UserProcessor implements ResetInterface
{
    private ?User $lastUser = null;
    private ?string $lastDomain = null;

    public function __construct(
        private readonly EcsUserProviderInterface $provider,
        private readonly ?string $domain = null,
    ) {
    }

    public function reset(): void
    {
        $this->lastUser = null;
        $this->lastDomain = null;
    }

    private function support(LogRecord $record): bool
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

        if ($ecsUser !== $this->lastUser) {
            $this->lastUser = $ecsUser;
            $this->lastDomain = $this->provider->getDomain() ?? $this->domain;

            if ($this->lastDomain !== null) {
                $ecsUser->setDomain($this->lastDomain);
            }
        }

        $context = $record->context;
        $context['user'] = $ecsUser;

        return $record->with(context: $context);
    }
}
