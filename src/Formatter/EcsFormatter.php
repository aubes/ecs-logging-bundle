<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\Formatter;

use Elastic\Monolog\Formatter\ElasticCommonSchemaFormatter;
use Monolog\LogRecord;

/**
 * Extends ElasticCommonSchemaFormatter to produce ECS-compliant output:
 * - log.level is lowercased and merged into the log object (ECS convention)
 * - ecs.version is configurable (upstream lib is hardcoded to 1.2.0)
 * - tags are configurable (upstream lib accepts them but the bundle did not expose them)
 */
final class EcsFormatter extends ElasticCommonSchemaFormatter
{
    /** @param list<string> $tags */
    public function __construct(private readonly string $ecsVersion = '9.3.0', array $tags = [])
    {
        parent::__construct($tags);
    }

    public function format(LogRecord $record): string
    {
        $json = parent::format($record);

        try {
            $data = \json_decode(\rtrim($json, "\n"), true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            // Safety net: parent::format() always returns a valid JSON object.
            // This branch is unreachable in practice but guards against upstream changes.
            return $json;
        }

        if (isset($data['log.level'])) {
            $data['log']['level'] = \strtolower($data['log.level']);
            unset($data['log.level']);
        }

        $data['ecs.version'] = $this->ecsVersion;

        return $this->toJson($data) . "\n";
    }
}
