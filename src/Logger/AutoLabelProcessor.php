<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\Logger;

use Monolog\LogRecord;

final class AutoLabelProcessor
{
    public const FIELD_TIMESTAMP = '@timestamp';
    public const FIELD_LABELS = 'labels';
    public const FIELD_MESSAGE = 'message';
    public const FIELD_TAGS = 'tags';
    public const FIELD_AGENT = 'agent';
    public const FIELD_AS = 'as';
    public const FIELD_CLIENT = 'client';
    public const FIELD_CLOUD = 'cloud';
    public const FIELD_CODE_SIGNATURE = 'code_signature';
    public const FIELD_CONTAINER = 'container';
    public const FIELD_DATA_STREAM = 'data_stream';
    public const FIELD_DESTINATION = 'destination';
    public const FIELD_DEVICE = 'device';
    public const FIELD_DLL = 'dll';
    public const FIELD_DNS = 'dns';
    public const FIELD_ECS = 'ecs';
    public const FIELD_ELF = 'elf';
    public const FIELD_ENTITY = 'entity';
    public const FIELD_EMAIL = 'email';
    public const FIELD_ERROR = 'error';
    public const FIELD_EVENT = 'event';
    public const FIELD_FAAS = 'faas';
    public const FIELD_GEN_AI = 'gen_ai';
    public const FIELD_FILE = 'file';
    public const FIELD_GEO = 'geo';
    public const FIELD_GROUP = 'group';
    public const FIELD_HASH = 'hash';
    public const FIELD_HOST = 'host';
    public const FIELD_HTTP = 'http';
    /**
     * Not a top-level ECS field set — `interface` is a sub-object of `observer.ingress/egress`.
     * Kept for BC but excluded from FIELDS_ALL.
     */
    public const FIELD_INTERFACE = 'interface';
    public const FIELD_LOG = 'log';
    public const FIELD_NETWORK = 'network';
    public const FIELD_OBSERVER = 'observer';
    public const FIELD_ORCHESTRATOR = 'orchestrator';
    public const FIELD_ORGANIZATION = 'organization';
    public const FIELD_OS = 'os';
    public const FIELD_PACKAGE = 'package';
    public const FIELD_PE = 'pe';
    public const FIELD_PROCESS = 'process';
    public const FIELD_REGISTRY = 'registry';
    public const FIELD_RELATED = 'related';
    public const FIELD_RISK = 'risk';
    public const FIELD_RULE = 'rule';
    public const FIELD_SERVER = 'server';
    public const FIELD_SERVICE = 'service';
    public const FIELD_SOURCE = 'source';
    public const FIELD_THREAT = 'threat';
    public const FIELD_TLS = 'tls';
    public const FIELD_SPAN = 'span';
    public const FIELD_TRACE = 'trace';
    /**
     * Not an ECS field set — bundle-internal transport key used by TracingProcessor.
     * ElasticCommonSchemaFormatter hardcodes context['tracing'] to detect Elastic\Types\Tracing objects.
     * Automatically excluded from auto-labeling via FIELDS_INTERNAL — users do not need to whitelist it.
     */
    public const FIELD_TRACING = 'tracing';
    public const FIELD_TRANSACTION = 'transaction';
    public const FIELD_URL = 'url';
    public const FIELD_USER = 'user';
    public const FIELD_USER_AGENT = 'user_agent';
    /**
     * Not a top-level ECS field set — `vlan` is a sub-object of `network.vlan`.
     * Kept for BC but excluded from FIELDS_ALL.
     */
    public const FIELD_VLAN = 'vlan';
    public const FIELD_VULNERABILITY = 'vulnerability';
    public const FIELD_X509 = 'x509';

    /**
     * Fields injected by bundle processors that must never be moved to labels, regardless of the user's field list.
     * AutoLabelProcessor always merges this list into its whitelist automatically.
     *
     * - FIELD_TRACING: non-ECS transport key required by ElasticCommonSchemaFormatter to serialize Tracing objects.
     * - FIELD_SPAN:    injected by TracingProcessor when span_id is provided.
     */
    public const FIELDS_INTERNAL = [
        self::FIELD_TRACING,
        self::FIELD_SPAN,
    ];

    public const MODE_BUNDLE = 'bundle';
    public const MODE_FULL = 'full';
    public const MODE_CUSTOM = 'custom';

    public const FIELDS_BUNDLE = [
        self::FIELD_CLIENT,
        self::FIELD_ERROR,
        self::FIELD_HOST,
        self::FIELD_HTTP,
        self::FIELD_LABELS,
        self::FIELD_LOG,
        self::FIELD_MESSAGE,
        self::FIELD_SERVICE,
        self::FIELD_TIMESTAMP,
        self::FIELD_TRACE,
        self::FIELD_TRANSACTION,
        self::FIELD_URL,
        self::FIELD_USER,
    ];

    public const FIELDS_ALL = [
        self::FIELD_TIMESTAMP,
        self::FIELD_LABELS,
        self::FIELD_MESSAGE,
        self::FIELD_TAGS,
        self::FIELD_AGENT,
        self::FIELD_AS,
        self::FIELD_CLIENT,
        self::FIELD_CLOUD,
        self::FIELD_CODE_SIGNATURE,
        self::FIELD_CONTAINER,
        self::FIELD_DATA_STREAM,
        self::FIELD_DESTINATION,
        self::FIELD_DEVICE,
        self::FIELD_DLL,
        self::FIELD_DNS,
        self::FIELD_ECS,
        self::FIELD_ELF,
        self::FIELD_ENTITY,
        self::FIELD_EMAIL,
        self::FIELD_ERROR,
        self::FIELD_EVENT,
        self::FIELD_FAAS,
        self::FIELD_FILE,
        self::FIELD_GEN_AI,
        self::FIELD_GEO,
        self::FIELD_GROUP,
        self::FIELD_HASH,
        self::FIELD_HOST,
        self::FIELD_HTTP,
        self::FIELD_LOG,
        self::FIELD_NETWORK,
        self::FIELD_OBSERVER,
        self::FIELD_ORCHESTRATOR,
        self::FIELD_ORGANIZATION,
        self::FIELD_PACKAGE,
        self::FIELD_PE,
        self::FIELD_PROCESS,
        self::FIELD_REGISTRY,
        self::FIELD_RELATED,
        self::FIELD_RISK,
        self::FIELD_RULE,
        self::FIELD_SERVER,
        self::FIELD_SERVICE,
        self::FIELD_SOURCE,
        self::FIELD_THREAT,
        self::FIELD_TLS,
        self::FIELD_SPAN,
        self::FIELD_TRACE,
        self::FIELD_TRANSACTION,
        self::FIELD_URL,
        self::FIELD_USER,
        self::FIELD_USER_AGENT,
        self::FIELD_VULNERABILITY,
        self::FIELD_X509,
    ];

    public const STRATEGY_SKIP = 'skip';
    public const STRATEGY_JSON = 'json';

    /** @var array<string, int> */
    private readonly array $ecsFields;
    private readonly bool $encodeAsJson;

    /** @param list<string> $fields */
    public function __construct(
        array $fields,
        private readonly bool $moveToLabels = false,
        string $nonScalarStrategy = self::STRATEGY_SKIP,
        private readonly bool $includeExtra = false,
    ) {
        $this->ecsFields = \array_flip(\array_merge($fields, self::FIELDS_INTERNAL));
        $this->encodeAsJson = $nonScalarStrategy === self::STRATEGY_JSON;
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $context = $record->context;
        $extra = $record->extra;

        $nonEcsContext = \array_diff_key($context, $this->ecsFields);
        $nonEcsExtra = $this->includeExtra ? \array_diff_key($extra, $this->ecsFields) : [];

        if (empty($nonEcsContext) && empty($nonEcsExtra)) {
            return $record;
        }

        $context = \array_diff_key($context, $nonEcsContext);
        $extra = \array_diff_key($extra, $nonEcsExtra);

        if ($this->moveToLabels) {
            $labels = \array_merge(
                $this->toScalarLabels($nonEcsContext),
                $this->toScalarLabels($nonEcsExtra),
            );
            if (!empty($labels)) {
                $existingLabels = $context['labels'] ?? [];
                if (!\is_array($existingLabels)) {
                    $existingLabels = [];
                }
                $context['labels'] = \array_merge($labels, $existingLabels);
            }
        }

        return $record->with(context: $context, extra: $extra);
    }

    /**
     * @param array<string, mixed> $fields
     *
     * @return array<string, scalar>
     */
    private function toScalarLabels(array $fields): array
    {
        $labels = [];

        foreach ($fields as $name => $value) {
            if (\is_scalar($value)) {
                $labels[$name] = $value;
                continue;
            }
            if ($this->encodeAsJson) {
                $encoded = \json_encode($value);
                if ($encoded !== false) {
                    $labels[$name] = $encoded;
                }
            }
        }

        return $labels;
    }
}
