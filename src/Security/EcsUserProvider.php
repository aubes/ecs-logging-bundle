<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\Security;

use Elastic\Types\User;
use Symfony\Component\Security\Core\Security;

class EcsUserProvider implements EcsUserProviderInterface
{
    protected Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @psalm-suppress InternalMethod
     */
    public function getUser(): ?User
    {
        $user = $this->security->getUser();

        if ($user !== null) {
            $ecsUser = new User();
            $ecsUser->setId($user->getUserIdentifier());

            return $ecsUser;
        }

        return null;
    }

    public function getDomain(): ?string
    {
        return null;
    }
}
