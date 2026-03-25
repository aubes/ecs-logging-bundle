<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\Tests\Security;

use Aubes\EcsLoggingBundle\Security\EcsUserProvider;
use Elastic\Types\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class EcsUserProviderTest extends TestCase
{
    public function testGetUserReturnsEcsUserWhenAuthenticated(): void
    {
        $symfonyUser = $this->createStub(UserInterface::class);
        $symfonyUser->method('getUserIdentifier')->willReturn('user@example.com');

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($symfonyUser);

        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $user = (new EcsUserProvider($tokenStorage))->getUser();

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('user@example.com', $user->jsonSerialize()['user']['name']);
    }

    public function testGetUserReturnsNullWhenNoToken(): void
    {
        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn(null);

        $this->assertNull((new EcsUserProvider($tokenStorage))->getUser());
    }

    public function testGetUserReturnsNullWhenTokenHasNoUser(): void
    {
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $this->assertNull((new EcsUserProvider($tokenStorage))->getUser());
    }

    public function testGetDomainReturnsNull(): void
    {
        $this->assertNull((new EcsUserProvider($this->createStub(TokenStorageInterface::class)))->getDomain());
    }

    public function testGetUserCachesResultForSameToken(): void
    {
        $symfonyUser = $this->createMock(UserInterface::class);
        $symfonyUser->expects($this->once())->method('getUserIdentifier')->willReturn('user@example.com');

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($symfonyUser);

        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $provider = new EcsUserProvider($tokenStorage);

        $user1 = $provider->getUser();
        $user2 = $provider->getUser();

        $this->assertSame($user1, $user2);
    }

    public function testGetUserInvalidatesCacheWhenTokenChanges(): void
    {
        $makeUser = function (string $id): UserInterface {
            $stub = $this->createStub(UserInterface::class);
            $stub->method('getUserIdentifier')->willReturn($id);

            return $stub;
        };

        $token1 = $this->createStub(TokenInterface::class);
        $token1->method('getUser')->willReturn($makeUser('user1@example.com'));

        $token2 = $this->createStub(TokenInterface::class);
        $token2->method('getUser')->willReturn($makeUser('user2@example.com'));

        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturnOnConsecutiveCalls($token1, $token2);

        $provider = new EcsUserProvider($tokenStorage);

        $user1 = $provider->getUser();
        $user2 = $provider->getUser();

        $this->assertNotSame($user1, $user2);
        $this->assertSame('user1@example.com', $user1?->jsonSerialize()['user']['name']);
        $this->assertSame('user2@example.com', $user2?->jsonSerialize()['user']['name']);
    }

    public function testResetClearsCache(): void
    {
        $symfonyUser = $this->createStub(UserInterface::class);
        $symfonyUser->method('getUserIdentifier')->willReturn('user@example.com');

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($symfonyUser);

        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $provider = new EcsUserProvider($tokenStorage);
        $userBefore = $provider->getUser();

        $provider->reset();
        $userAfter = $provider->getUser();

        $this->assertNotSame($userBefore, $userAfter);
    }
}
