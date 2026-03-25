<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\Tests\Formatter;

use Aubes\EcsLoggingBundle\Formatter\EcsFormatter;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;

class EcsFormatterTest extends TestCase
{
    private function buildRecord(Level $level = Level::Info): LogRecord
    {
        return new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: $level,
            message: 'test message',
        );
    }

    public function testLogLevelIsLowercaseInsideLogObject(): void
    {
        $formatter = new EcsFormatter();
        $data = \json_decode($formatter->format($this->buildRecord(Level::Info)), true);

        $this->assertSame('info', $data['log']['level']);
    }

    public function testDotNotationLogLevelKeyIsRemoved(): void
    {
        $formatter = new EcsFormatter();
        $data = \json_decode($formatter->format($this->buildRecord(Level::Info)), true);

        $this->assertArrayNotHasKey('log.level', $data);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('levelProvider')]
    public function testAllLevelsAreLowercase(Level $level, string $expected): void
    {
        $formatter = new EcsFormatter();
        $data = \json_decode($formatter->format($this->buildRecord($level)), true);

        $this->assertSame($expected, $data['log']['level']);
    }

    /** @return array<string, array{Level, string}> */
    public static function levelProvider(): array
    {
        return [
            'debug' => [Level::Debug, 'debug'],
            'info' => [Level::Info, 'info'],
            'notice' => [Level::Notice, 'notice'],
            'warning' => [Level::Warning, 'warning'],
            'error' => [Level::Error, 'error'],
            'critical' => [Level::Critical, 'critical'],
            'alert' => [Level::Alert, 'alert'],
            'emergency' => [Level::Emergency, 'emergency'],
        ];
    }

    public function testOutputIsValidJson(): void
    {
        $formatter = new EcsFormatter();
        $output = $formatter->format($this->buildRecord());

        $this->assertNotNull(\json_decode($output, true));
        $this->assertStringEndsWith("\n", $output);
    }

    public function testEcsVersionDefaultIs9x(): void
    {
        $formatter = new EcsFormatter();
        $data = \json_decode($formatter->format($this->buildRecord()), true);

        $this->assertSame('9.3.0', $data['ecs.version']);
    }

    public function testEcsVersionIsOverridable(): void
    {
        $formatter = new EcsFormatter('8.11.0');
        $data = \json_decode($formatter->format($this->buildRecord()), true);

        $this->assertSame('8.11.0', $data['ecs.version']);
    }

    public function testTagsAreAbsentByDefault(): void
    {
        $formatter = new EcsFormatter();
        $data = \json_decode($formatter->format($this->buildRecord()), true);

        $this->assertArrayNotHasKey('tags', $data);
    }

    public function testTagsAreAddedToOutput(): void
    {
        $formatter = new EcsFormatter('9.3.0', ['env:prod', 'region:eu-west-1']);
        $data = \json_decode($formatter->format($this->buildRecord()), true);

        $this->assertSame(['env:prod', 'region:eu-west-1'], $data['tags']);
    }

    public function testOtherFieldsArePreserved(): void
    {
        $formatter = new EcsFormatter();
        $data = \json_decode($formatter->format($this->buildRecord()), true);

        $this->assertArrayHasKey('@timestamp', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('ecs.version', $data);
        $this->assertArrayHasKey('log', $data);
        $this->assertArrayHasKey('logger', $data['log']);
        $this->assertArrayHasKey('level', $data['log']);
    }
}
