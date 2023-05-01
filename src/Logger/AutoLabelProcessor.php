<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\Logger;

class AutoLabelProcessor
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
    public const FIELD_EMAIL = 'email';
    public const FIELD_ERROR = 'error';
    public const FIELD_EVENT = 'event';
    public const FIELD_FAAS = 'faas';
    public const FIELD_FILE = 'file';
    public const FIELD_GEO = 'geo';
    public const FIELD_GROUP = 'group';
    public const FIELD_HASH = 'hash';
    public const FIELD_HOST = 'host';
    public const FIELD_HTTP = 'http';
    public const FIELD_INTERFACE = 'interface';
    public const FIELD_LOG = 'log';
    public const FIELD_MATCHO = 'matcho';
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
    public const FIELD_TRACING = 'tracing';
    public const FIELD_TRANSACTION = 'transaction';
    public const FIELD_URL = 'url';
    public const FIELD_USER = 'user';
    public const FIELD_USER_AGENT = 'user_agent';
    public const FIELD_VLAN = 'vlan';
    public const FIELD_VULNERABILITY = 'vulnerability';
    public const FIELD_X509 = 'x509';

    public const FIELDS_MINIMAL = [
        self::FIELD_LOG,
        self::FIELD_MESSAGE,
        self::FIELD_SERVICE,
        self::FIELD_TIMESTAMP,
    ];

    public const FIELDS_BUNDLE = [
        self::FIELD_ERROR,
        self::FIELD_LABELS,
        self::FIELD_LOG,
        self::FIELD_MESSAGE,
        self::FIELD_SERVICE,
        self::FIELD_TIMESTAMP,
        self::FIELD_TRACE,
        self::FIELD_TRACING,
        self::FIELD_TRANSACTION,
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
        self::FIELD_EMAIL,
        self::FIELD_ERROR,
        self::FIELD_EVENT,
        self::FIELD_FAAS,
        self::FIELD_FILE,
        self::FIELD_GEO,
        self::FIELD_GROUP,
        self::FIELD_HASH,
        self::FIELD_HOST,
        self::FIELD_HTTP,
        self::FIELD_INTERFACE,
        self::FIELD_LOG,
        self::FIELD_MATCHO,
        self::FIELD_NETWORK,
        self::FIELD_OBSERVER,
        self::FIELD_ORCHESTRATOR,
        self::FIELD_ORGANIZATION,
        self::FIELD_OS,
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
        self::FIELD_TRACING,
        self::FIELD_TRANSACTION,
        self::FIELD_URL,
        self::FIELD_USER,
        self::FIELD_USER_AGENT,
        self::FIELD_VLAN,
        self::FIELD_VULNERABILITY,
        self::FIELD_X509,
    ];

    protected array $ecsFields;

    public function __construct(array $fields)
    {
        $this->ecsFields = $fields;
    }

    public function __invoke(array $record): array
    {
        foreach ($record['context'] as $contextName => $contextValue) {
            if (!\in_array($contextName, $this->ecsFields)) {
                $record['context']['labels'][$contextName] = $contextValue;
                unset($record['context'][$contextName]);
            }
        }

        return $record;
    }
}
