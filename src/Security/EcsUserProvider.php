<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\Security;

use Elastic\Types\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class EcsUserProvider implements EcsUserProviderInterface
{
    public function __construct(private readonly TokenStorageInterface $tokenStorage)
    {
    }

    /**
     * @psalm-suppress InternalMethod
     */
    public function getUser(): ?User
    {
        $user = $this->tokenStorage->getToken()?->getUser();

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
