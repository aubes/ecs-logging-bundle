<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\Security;

use Elastic\Types\User;

interface EcsUserProviderInterface
{
    /**
     * Returns the ECS User object for the current security context, or null if unauthenticated.
     *
     * WARNING: UserProcessor may call setDomain() on the returned object. Implementations must
     * therefore return a fresh User instance on each call, or implement ResetInterface to clear
     * any cached instance between requests (required in FrankenPHP worker mode).
     */
    public function getUser(): ?User;

    /**
     * Returns the domain to set on the User object, or null to use the processor's configured default.
     */
    public function getDomain(): ?string;
}
