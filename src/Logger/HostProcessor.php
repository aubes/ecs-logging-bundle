<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\Logger;

use Monolog\LogRecord;

final class HostProcessor
{
    /** @var array<string, mixed> */
    private readonly array $host;

    /** @param string[] $ip */
    public function __construct(
        ?string $name = null,
        array $ip = [],
        bool $resolveIp = false,
        ?string $architecture = null,
    ) {
        $hostname = \gethostname();
        $resolvedName = $name ?? ($hostname !== false ? $hostname : null);

        $host = [];

        if ($resolvedName !== null) {
            $host['name'] = $resolvedName;
        }

        if (!empty($ip)) {
            $host['ip'] = $ip;
        } elseif ($resolveIp && $resolvedName !== null) {
            $resolved = \gethostbyname($resolvedName);

            if ($resolved !== $resolvedName) {
                $host['ip'] = [$resolved];
            }
        }

        $host['architecture'] = $architecture ?? (\php_uname('m') ?: null);

        $this->host = \array_filter($host, static fn (mixed $val): bool => $val !== null);
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        if (isset($record->context['host'])) {
            return $record;
        }

        $context = $record->context;
        $context['host'] = $this->host;

        return $record->with(context: $context);
    }
}
