<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\Tests\Security;

use Aubes\EcsLoggingBundle\Security\EcsUserProvider;
use Elastic\Types\User;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class EcsUserProviderTest extends TestCase
{
    use ProphecyTrait;

    public function testGetUserReturnsEcsUserWhenAuthenticated(): void
    {
        $symfonyUser = $this->prophesize(UserInterface::class);
        $symfonyUser->getUserIdentifier()->willReturn('user@example.com');

        $token = $this->prophesize(TokenInterface::class);
        $token->getUser()->willReturn($symfonyUser->reveal());

        $tokenStorage = $this->prophesize(TokenStorageInterface::class);
        $tokenStorage->getToken()->willReturn($token->reveal());

        $provider = new EcsUserProvider($tokenStorage->reveal());
        $user = $provider->getUser();

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('user@example.com', $user->jsonSerialize()['user']['id']);
    }

    public function testGetUserReturnsNullWhenNoToken(): void
    {
        $tokenStorage = $this->prophesize(TokenStorageInterface::class);
        $tokenStorage->getToken()->willReturn(null);

        $provider = new EcsUserProvider($tokenStorage->reveal());

        $this->assertNull($provider->getUser());
    }

    public function testGetUserReturnsNullWhenTokenHasNoUser(): void
    {
        $token = $this->prophesize(TokenInterface::class);
        $token->getUser()->willReturn(null);

        $tokenStorage = $this->prophesize(TokenStorageInterface::class);
        $tokenStorage->getToken()->willReturn($token->reveal());

        $provider = new EcsUserProvider($tokenStorage->reveal());

        $this->assertNull($provider->getUser());
    }

    public function testGetDomainReturnsNull(): void
    {
        $tokenStorage = $this->prophesize(TokenStorageInterface::class);

        $provider = new EcsUserProvider($tokenStorage->reveal());

        $this->assertNull($provider->getDomain());
    }
}
