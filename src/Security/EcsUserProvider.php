<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\Security;

use Elastic\Types\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class EcsUserProvider implements EcsUserProviderInterface
{
    protected TokenStorageInterface $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function getUser(): ?User
    {
        $token = $this->tokenStorage->getToken();

        if ($token === null) {
            return null;
        }

        $user = $token->getUser();

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
