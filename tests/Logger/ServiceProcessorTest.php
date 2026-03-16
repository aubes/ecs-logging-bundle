<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\Tests\Logger;

use Aubes\EcsLoggingBundle\Logger\ServiceProcessor;
use Elastic\Types\Service;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class ServiceProcessorTest extends TestCase
{
    use ProphecyTrait;

    private function createRecord(array $context): LogRecord
    {
        return new LogRecord(
            new \DateTimeImmutable(),
            'channel',
            Level::Info,
            'message',
            $context
        );
    }

    public function testServiceIsInjectedWhenAbsent(): void
    {
        $service = new Service();
        $service->setName('my-app');
        $service->setVersion('1.0.0');

        $processor = new ServiceProcessor($service);

        $record = $this->createRecord([]);
        $record = $processor($record);

        $this->assertArrayHasKey('service', $record->context);
        $this->assertSame($service, $record->context['service']);
    }

    public function testServiceIsNotOverriddenWhenAlreadyPresent(): void
    {
        $injectedService = new Service();
        $injectedService->setName('injected');

        $existingService = new Service();
        $existingService->setName('existing');

        $processor = new ServiceProcessor($injectedService);

        $record = $this->createRecord(['service' => $existingService]);
        $record = $processor($record);

        $this->assertArrayHasKey('service', $record->context);
        $this->assertSame($existingService, $record->context['service']);
        $this->assertNotSame($injectedService, $record->context['service']);
    }

    public function testOtherContextFieldsArePreserved(): void
    {
        $service = new Service();

        $processor = new ServiceProcessor($service);

        $record = $this->createRecord(['foo' => 'bar']);
        $record = $processor($record);

        $this->assertArrayHasKey('service', $record->context);
        $this->assertArrayHasKey('foo', $record->context);
        $this->assertSame('bar', $record->context['foo']);
    }
}
