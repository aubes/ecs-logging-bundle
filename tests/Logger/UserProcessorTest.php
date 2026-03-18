<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\Tests\Logger;

use Aubes\EcsLoggingBundle\Logger\UserProcessor;
use Aubes\EcsLoggingBundle\Security\EcsUserProviderInterface;
use Elastic\Types\User;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class UserProcessorTest extends TestCase
{
    use ProphecyTrait;

    public function testWithUserProcessor(): void
    {
        $user = $this->prophesize(User::class);

        $provider = $this->prophesize(EcsUserProviderInterface::class);
        $provider->getDomain()->willReturn('in_memory');
        $provider->getUser()->willReturn($user->reveal());

        $processor = new UserProcessor($provider->reveal(), 'unknown');

        $record = new LogRecord(
            new \DateTimeImmutable(),
            'channel',
            Level::Info,
            'message',
            []
        );

        $record = $processor($record);

        $this->assertArrayHasKey('user', $record->context);
        $this->assertInstanceOf(User::class, $record->context['user']);
    }

    public function testWithoutUserProcessor(): void
    {
        $provider = $this->prophesize(EcsUserProviderInterface::class);
        $provider->getDomain()->willReturn('in_memory');
        $provider->getUser()->willReturn(null);

        $processor = new UserProcessor($provider->reveal(), 'unknown');

        $record = new LogRecord(
            new \DateTimeImmutable(),
            'channel',
            Level::Info,
            'message',
            []
        );

        $record = $processor($record);

        $this->assertArrayNotHasKey('user', $record->context);
    }

    public function testSkipsWhenUserAlreadyInContext(): void
    {
        $provider = $this->prophesize(EcsUserProviderInterface::class);

        $processor = new UserProcessor($provider->reveal(), 'unknown');

        $record = new LogRecord(
            new \DateTimeImmutable(),
            'channel',
            Level::Info,
            'message',
            [
                'user' => [
                    'id' => 'User Id',
                ],
            ]
        );

        $record = $processor($record);

        $this->assertArrayHasKey('user', $record->context);
        $this->assertArrayHasKey('id', $record->context['user']);
        $this->assertSame('User Id', $record->context['user']['id']);
    }

    public function testDomainFromProviderTakesPriority(): void
    {
        $user = new User();

        $provider = $this->prophesize(EcsUserProviderInterface::class);
        $provider->getDomain()->willReturn('provider_domain');
        $provider->getUser()->willReturn($user);

        $processor = new UserProcessor($provider->reveal(), 'constructor_domain');

        $record = new LogRecord(
            new \DateTimeImmutable(),
            'channel',
            Level::Info,
            'message',
            []
        );

        $record = $processor($record);

        $this->assertArrayHasKey('user', $record->context);
        $this->assertSame('provider_domain', $record->context['user']->jsonSerialize()['user']['domain']);
    }

    public function testDomainFallbackToConstructor(): void
    {
        $user = new User();

        $provider = $this->prophesize(EcsUserProviderInterface::class);
        $provider->getDomain()->willReturn(null);
        $provider->getUser()->willReturn($user);

        $processor = new UserProcessor($provider->reveal(), 'constructor_domain');

        $record = new LogRecord(
            new \DateTimeImmutable(),
            'channel',
            Level::Info,
            'message',
            []
        );

        $record = $processor($record);

        $this->assertArrayHasKey('user', $record->context);
        $this->assertSame('constructor_domain', $record->context['user']->jsonSerialize()['user']['domain']);
    }

    public function testNoDomainWhenBothAreNull(): void
    {
        $user = new User();

        $provider = $this->prophesize(EcsUserProviderInterface::class);
        $provider->getDomain()->willReturn(null);
        $provider->getUser()->willReturn($user);

        $processor = new UserProcessor($provider->reveal(), null);

        $record = new LogRecord(
            new \DateTimeImmutable(),
            'channel',
            Level::Info,
            'message',
            []
        );

        $record = $processor($record);

        $this->assertArrayHasKey('user', $record->context);
        $this->assertArrayNotHasKey('domain', $record->context['user']->jsonSerialize()['user'] ?? []);
    }
}
