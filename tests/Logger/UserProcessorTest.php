<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\Tests\Logger;

use Aubes\EcsLoggingBundle\Logger\UserProcessor;
use Aubes\EcsLoggingBundle\Security\EcsUserProviderInterface;
use Elastic\Types\User;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class UserProcessorTest extends TestCase
{
    use ProphecyTrait;

    public function testWithUserTransformer()
    {
        $user = $this->prophesize(User::class);

        $provider = $this->prophesize(EcsUserProviderInterface::class);
        $provider->getDomain()->willReturn('in_memory');
        $provider->getUser()->willReturn($user->reveal());

        $processor = new UserProcessor($provider->reveal(), 'unknown');

        $record = [
            'context' => [],
        ];

        $record = $processor($record);

        $this->assertArrayHasKey('context', $record);
        $this->assertArrayHasKey('user', $record['context']);
        $this->assertInstanceOf(User::class, $record['context']['user']);
    }

    public function testWithoutUserTransformer()
    {
        $provider = $this->prophesize(EcsUserProviderInterface::class);
        $provider->getDomain()->willReturn('in_memory');
        $provider->getUser()->willReturn(null);

        $processor = new UserProcessor($provider->reveal(), 'unknown');

        $record = [
            'context' => [],
        ];

        $record = $processor($record);

        $this->assertArrayHasKey('context', $record);
        $this->assertArrayNotHasKey('user', $record['context']);
    }

    public function testUserDefinedTransformer()
    {
        $user = $this->prophesize(User::class);

        $provider = $this->prophesize(EcsUserProviderInterface::class);
        $provider->getDomain()->willReturn('in_memory');
        $provider->getUser()->willReturn($user->reveal());

        $processor = new UserProcessor($provider->reveal(), 'unknown');

        $record = [
            'context' => [
                'user' => [
                    'id' => 'User Id',
                ],
            ],
        ];

        $record = $processor($record);

        $this->assertArrayHasKey('context', $record);
        $this->assertArrayHasKey('user', $record['context']);
        $this->assertArrayHasKey('id', $record['context']['user']);
        $this->assertSame('User Id', $record['context']['user']['id']);
    }

    public function testDomain()
    {
        // todo
    }
}
