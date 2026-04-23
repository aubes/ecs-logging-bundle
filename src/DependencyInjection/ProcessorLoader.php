<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\DependencyInjection;

use Aubes\EcsLoggingBundle\Logger\AutoLabelProcessor;
use Aubes\EcsLoggingBundle\Logger\CorrelationIdProcessor;
use Aubes\EcsLoggingBundle\Logger\ErrorProcessor;
use Aubes\EcsLoggingBundle\Logger\HostProcessor;
use Aubes\EcsLoggingBundle\Logger\HttpRequestProcessor;
use Aubes\EcsLoggingBundle\Logger\ServiceProcessor;
use Aubes\EcsLoggingBundle\Logger\TracingProcessor;
use Aubes\EcsLoggingBundle\Logger\UserProcessor;
use Aubes\EcsLoggingBundle\Security\EcsUserProvider;
use Elastic\Types\Service;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class ProcessorLoader
{
    /** @param array<string, mixed> $config */
    public function registerAutoLabelProcessor(array $config, ContainerBuilder $builder): void
    {
        if (!isset($config['processor']['auto_label']) || !$config['processor']['auto_label']['enabled']) {
            return;
        }

        $processorConfig = $config['processor']['auto_label'];

        $fields = match ($processorConfig['mode']) {
            AutoLabelProcessor::MODE_BUNDLE => AutoLabelProcessor::FIELDS_BUNDLE,
            AutoLabelProcessor::MODE_FULL => AutoLabelProcessor::FIELDS_ALL,
            AutoLabelProcessor::MODE_CUSTOM => $processorConfig['fields'],
            default => throw new \LogicException(\sprintf('Unexpected auto_label mode "%s".', $processorConfig['mode'])),
        };

        $processor = new Definition(AutoLabelProcessor::class);
        $processor->setArgument('$fields', $fields);
        $processor->setArgument('$moveToLabels', $processorConfig['move_to_labels']);
        $processor->setArgument('$nonScalarStrategy', $processorConfig['non_scalar_strategy']);
        $processor->setArgument('$includeExtra', $processorConfig['include_extra']);

        $this->configureMonologProcessor($config, $processorConfig, $processor, -10);

        $builder->setDefinition('.ecs_logging.processor.auto_label', $processor);
    }

    /** @param array<string, mixed> $config */
    public function registerErrorProcessor(array $config, ContainerBuilder $builder): void
    {
        if (!isset($config['processor']['error']) || !$config['processor']['error']['enabled']) {
            return;
        }

        $processorConfig = $config['processor']['error'];

        $processor = new Definition(ErrorProcessor::class);
        $processor->setArgument('$fieldName', $processorConfig['field_name']);
        $processor->setArgument('$mapExceptionKey', $processorConfig['map_exception_key']);

        $this->configureMonologProcessor($config, $processorConfig, $processor);

        $builder->setDefinition('.ecs_logging.processor.error', $processor);
    }

    /** @param array<string, mixed> $config */
    public function registerHostProcessor(array $config, ContainerBuilder $builder): void
    {
        if (!isset($config['processor']['host']) || !$config['processor']['host']['enabled']) {
            return;
        }

        $processorConfig = $config['processor']['host'];

        $processor = new Definition(HostProcessor::class);
        $processor->setArgument('$name', $processorConfig['name']);
        $processor->setArgument('$ip', $processorConfig['ip']);
        $processor->setArgument('$resolveIp', $processorConfig['resolve_ip']);
        $processor->setArgument('$architecture', $processorConfig['architecture']);

        $this->configureMonologProcessor($config, $processorConfig, $processor);

        $builder->setDefinition('.ecs_logging.processor.host', $processor);
    }

    /** @param array<string, mixed> $config */
    public function registerHttpRequestProcessor(array $config, ContainerBuilder $builder): void
    {
        if (!isset($config['processor']['http_request']) || !$config['processor']['http_request']['enabled']) {
            return;
        }

        $processorConfig = $config['processor']['http_request'];

        $processor = new Definition(HttpRequestProcessor::class);
        $processor->setArgument('$requestStack', new Reference('request_stack'));
        $processor->setArgument('$includeFullUrl', $processorConfig['include_full_url']);
        $processor->setArgument('$includeClientIp', $processorConfig['include_client_ip']);
        $processor->setArgument('$includeReferrer', $processorConfig['include_referrer']);
        $processor->addTag('kernel.reset', ['method' => 'reset']);

        $this->configureMonologProcessor($config, $processorConfig, $processor);

        $builder->setDefinition('.ecs_logging.processor.http_request', $processor);
    }

    /** @param array<string, mixed> $config */
    public function registerServiceProcessor(array $config, ContainerBuilder $builder): void
    {
        if (!isset($config['processor']['service']) || !$config['processor']['service']['enabled']) {
            return;
        }

        $processorConfig = $config['processor']['service'];

        $processor = new Definition(ServiceProcessor::class);
        $processor->setArgument('$service', $this->buildServiceDefinition($processorConfig));

        $this->configureMonologProcessor($config, $processorConfig, $processor);

        $builder->setDefinition('.ecs_logging.processor.service', $processor);
    }

    /** @param array<string, mixed> $config */
    public function registerTracingProcessor(array $config, ContainerBuilder $builder): void
    {
        if (!isset($config['processor']['tracing']) || !$config['processor']['tracing']['enabled']) {
            return;
        }

        $processorConfig = $config['processor']['tracing'];

        $processor = new Definition(TracingProcessor::class);
        $processor->setArgument('$fieldName', $processorConfig['field_name']);
        $processor->setArgument('$mode', $processorConfig['mode']);

        $this->configureMonologProcessor($config, $processorConfig, $processor);

        $builder->setDefinition('.ecs_logging.processor.tracing', $processor);
    }

    /** @param array<string, mixed> $config */
    public function registerUserProcessor(array $config, ContainerBuilder $builder): void
    {
        if (!isset($config['processor']['user']) || !$config['processor']['user']['enabled']) {
            return;
        }

        $processorConfig = $config['processor']['user'];

        $processor = new Definition(UserProcessor::class);
        $processor->setArgument('$provider', $this->resolveUserProvider($processorConfig));
        $processor->setArgument('$domain', $processorConfig['domain']);
        $processor->addTag('kernel.reset', ['method' => 'reset']);

        $this->configureMonologProcessor($config, $processorConfig, $processor);

        $builder->setDefinition('.ecs_logging.processor.user', $processor);
    }

    /** @param array<string, mixed> $processorConfig */
    private function buildServiceDefinition(array $processorConfig): Definition
    {
        $service = new Definition(Service::class);

        $setters = [
            'name' => 'setName',
            'address' => 'setAddress',
            'version' => 'setVersion',
            'ephemeral_id' => 'setEphemeralId',
            'id' => 'setId',
            'node_name' => 'setNodeName',
            'state' => 'setState',
            'type' => 'setType',
        ];

        foreach ($setters as $key => $method) {
            if (isset($processorConfig[$key])) {
                $service->addMethodCall($method, [$processorConfig[$key]]);
            }
        }

        return $service;
    }

    /** @param array<string, mixed> $processorConfig */
    private function resolveUserProvider(array $processorConfig): Definition|Reference
    {
        if (($processorConfig['provider'] ?? null) !== null) {
            return new Reference($processorConfig['provider']);
        }

        if (!\interface_exists(\Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface::class)) {
            throw new \LogicException('The ecs_logging user processor requires symfony/security-core. Install it or configure a custom provider via ecs_logging.processor.user.provider.');
        }

        $provider = new Definition(EcsUserProvider::class);
        $provider->setArgument('$tokenStorage', new Reference('security.token_storage'));
        $provider->addTag('kernel.reset', ['method' => 'reset']);

        return $provider;
    }

    /** @param array<string, mixed> $config */
    public function registerCorrelationIdProcessor(array $config, ContainerBuilder $builder): void
    {
        if (!isset($config['processor']['correlation_id']) || !$config['processor']['correlation_id']['enabled']) {
            return;
        }

        $processorConfig = $config['processor']['correlation_id'];

        $processor = new Definition(CorrelationIdProcessor::class);
        $processor->setArgument('$fieldName', $processorConfig['field_name']);
        $processor->setArgument('$target', $processorConfig['target']);

        $this->configureMonologProcessor($config, $processorConfig, $processor);

        $builder->setDefinition('.ecs_logging.processor.correlation_id', $processor);
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $configOverride
     */
    private function configureMonologProcessor(array $config, array $configOverride, Definition $processor, int $priority = 0): void
    {
        $processorHasChannels = !empty($configOverride['channels']);
        $processorHasHandlers = !empty($configOverride['handlers']);

        if ($processorHasChannels || $processorHasHandlers) {
            $channels = $configOverride['channels'];
            $handlers = $configOverride['handlers'];
        } else {
            $channels = $config['monolog']['channels'];
            $handlers = $config['monolog']['handlers'];
        }

        if (!empty($channels) && !empty($handlers)) {
            throw new InvalidConfigurationException('ecs_logging: a processor cannot target both channels and handlers simultaneously. Configure one or the other.');
        }

        if (empty($channels) && empty($handlers)) {
            throw new InvalidConfigurationException('ecs_logging: a processor is enabled but has no channel or handler configured. It will never be invoked.');
        }

        foreach ($channels as $channel) {
            $processor->addTag('monolog.processor', ['channel' => $channel, 'priority' => $priority]);
        }

        foreach ($handlers as $handler) {
            $processor->addTag('monolog.processor', ['handler' => $handler, 'priority' => $priority]);
        }
    }
}
