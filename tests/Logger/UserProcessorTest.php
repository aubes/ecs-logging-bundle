<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\Tests\Logger;

use Aubes\EcsLoggingBundle\Logger\UserProcessor;
use Aubes\EcsLoggingBundle\Security\EcsUserProviderInterface;
use Elastic\Types\User;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;

class UserProcessorTest extends TestCase
{
    /** @param array<string, mixed> $context */
    private function makeRecord(array $context = []): LogRecord
    {
        return new LogRecord(new \DateTimeImmutable(), 'channel', Level::Info, 'message', $context);
    }

    private function makeProvider(?User $user, ?string $domain = null): EcsUserProviderInterface
    {
        $provider = $this->createStub(EcsUserProviderInterface::class);
        $provider->method('getUser')->willReturn($user);
        $provider->method('getDomain')->willReturn($domain);

        return $provider;
    }

    public function testWithUserProcessor(): void
    {
        $processor = new UserProcessor($this->makeProvider(new User(), 'in_memory'), 'unknown');

        $record = $processor($this->makeRecord());

        $this->assertArrayHasKey('user', $record->context);
        $this->assertInstanceOf(User::class, $record->context['user']);
    }

    public function testWithoutUserProcessor(): void
    {
        $processor = new UserProcessor($this->makeProvider(null, 'in_memory'), 'unknown');

        $record = $processor($this->makeRecord());

        $this->assertArrayNotHasKey('user', $record->context);
    }

    public function testSkipsWhenUserAlreadyInContext(): void
    {
        $processor = new UserProcessor($this->createStub(EcsUserProviderInterface::class), 'unknown');

        $record = $processor($this->makeRecord(['user' => ['id' => 'User Id']]));

        $this->assertSame('User Id', $record->context['user']['id']);
    }

    public function testDomainFromProviderTakesPriority(): void
    {
        $processor = new UserProcessor($this->makeProvider(new User(), 'provider_domain'), 'constructor_domain');

        $record = $processor($this->makeRecord());

        $this->assertSame('provider_domain', $record->context['user']->jsonSerialize()['user']['domain']);
    }

    public function testDomainFallbackToConstructor(): void
    {
        $processor = new UserProcessor($this->makeProvider(new User(), null), 'constructor_domain');

        $record = $processor($this->makeRecord());

        $this->assertSame('constructor_domain', $record->context['user']->jsonSerialize()['user']['domain']);
    }

    public function testNoDomainWhenBothAreNull(): void
    {
        $processor = new UserProcessor($this->makeProvider(new User(), null), null);

        $record = $processor($this->makeRecord());

        $this->assertArrayNotHasKey('domain', $record->context['user']->jsonSerialize()['user'] ?? []);
    }

    public function testResetClearsDomainCache(): void
    {
        $user = new User();
        $domainCallCount = 0;

        $provider = $this->createStub(EcsUserProviderInterface::class);
        $provider->method('getUser')->willReturn($user);
        $provider->method('getDomain')->willReturnCallback(static function () use (&$domainCallCount): ?string {
            ++$domainCallCount;

            return null;
        });

        $processor = new UserProcessor($provider);

        $processor($this->makeRecord());
        $this->assertSame(1, $domainCallCount); // getDomain resolved on first invocation

        $processor($this->makeRecord());
        /* @phpstan-ignore method.alreadyNarrowedType (cache hit, getDomain not re-called) */
        $this->assertSame(1, $domainCallCount);

        $processor->reset();

        $processor($this->makeRecord());
        /* @phpstan-ignore method.impossibleType (cache cleared, getDomain resolved again) */
        $this->assertSame(2, $domainCallCount);
    }
}
