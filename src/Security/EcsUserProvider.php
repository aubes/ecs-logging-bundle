<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\Security;

use Elastic\Types\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Contracts\Service\ResetInterface;

final class EcsUserProvider implements EcsUserProviderInterface, ResetInterface
{
    private ?User $cachedUser = null;
    /** @var null|\WeakReference<TokenInterface> */
    private ?\WeakReference $cachedToken = null;

    public function __construct(private readonly TokenStorageInterface $tokenStorage)
    {
    }

    public function getUser(): ?User
    {
        $token = $this->tokenStorage->getToken();

        if ($token === null) {
            return null;
        }

        if ($this->cachedToken?->get() === $token) {
            return $this->cachedUser;
        }

        $this->cachedToken = \WeakReference::create($token);

        $user = $token->getUser();
        if ($user === null) {
            $this->cachedUser = null;

            return null;
        }

        $ecsUser = new User();
        $ecsUser->setName($user->getUserIdentifier());
        $this->cachedUser = $ecsUser;

        return $ecsUser;
    }

    public function reset(): void
    {
        $this->cachedUser = null;
        $this->cachedToken = null;
    }

    public function getDomain(): ?string
    {
        return null;
    }
}
