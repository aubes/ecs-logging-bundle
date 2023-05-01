<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\DependencyInjection;

use Aubes\EcsLoggingBundle\Logger\AutoLabelProcessor;
use Aubes\EcsLoggingBundle\Logger\ErrorProcessor;
use Aubes\EcsLoggingBundle\Logger\ServiceProcessor;
use Aubes\EcsLoggingBundle\Logger\TracingProcessor;
use Aubes\EcsLoggingBundle\Logger\UserProcessor;
use Aubes\EcsLoggingBundle\Security\EcsUserProvider;
use Elastic\Types\Service;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @SuppressWarnings(PMD.CouplingBetweenObjects)
 * @SuppressWarnings(PMD.NPathComplexity)
 * @SuppressWarnings(PMD.CyclomaticComplexity)
 * @SuppressWarnings(PMD.ElseExpression)
 */
class EcsLoggingExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.yaml');

        if (isset($config['processor'])) {
            $this->configureAutoLabelProcessor($config, $container);
            $this->configureErrorProcessor($config, $container);
            $this->configureServiceProcessor($config, $container);
            $this->configureTracingProcessor($config, $container);
            $this->configureUserProcessor($config, $container);
        }
    }

    protected function configureAutoLabelProcessor(array $config, ContainerBuilder $container): void
    {
        if (!isset($config['processor']['auto_label']) || !$config['processor']['auto_label']['enabled']) {
            return;
        }

        $processorConfig = $config['processor']['auto_label'];

        $processor = new Definition(AutoLabelProcessor::class);
        $processor->setArgument('$fields', $processorConfig['fields']);

        $this->configureMonologProcessor($config, $processorConfig, $processor);

        $container->setDefinition('.ecs_logging.processor.auto_label', $processor);
    }

    protected function configureErrorProcessor(array $config, ContainerBuilder $container): void
    {
        if (!isset($config['processor']['error']) || !$config['processor']['error']['enabled']) {
            return;
        }

        $processorConfig = $config['processor']['error'];

        $processor = new Definition(ErrorProcessor::class);
        $processor->setArgument('$fieldName', $processorConfig['field_name']);

        $this->configureMonologProcessor($config, $processorConfig, $processor);

        $container->setDefinition('ecs_logging.processor.error', $processor);
    }

    protected function configureServiceProcessor(array $config, ContainerBuilder $container): void
    {
        if (!isset($config['processor']['service']) || !$config['processor']['service']['enabled']) {
            return;
        }

        $processorConfig = $config['processor']['service'];

        $processor = new Definition(ServiceProcessor::class);

        $this->configureMonologProcessor($config, $processorConfig, $processor);

        $service = new Definition(Service::class);

        if (isset($processorConfig['name'])) {
            $service->addMethodCall('setName', [$processorConfig['name']]);
        }

        if (isset($processorConfig['address'])) {
            $service->addMethodCall('setAddress', [$processorConfig['address']]);
        }

        if (isset($processorConfig['version'])) {
            $service->addMethodCall('setVersion', [$processorConfig['version']]);
        }

        if (isset($processorConfig['ephemeral_id'])) {
            $service->addMethodCall('setEphemeralId', [$processorConfig['ephemeral_id']]);
        }

        if (isset($processorConfig['id'])) {
            $service->addMethodCall('setId', [$processorConfig['id']]);
        }

        if (isset($processorConfig['node_name'])) {
            $service->addMethodCall('setNodeName', [$processorConfig['node_name']]);
        }

        if (isset($processorConfig['state'])) {
            $service->addMethodCall('setState', [$processorConfig['state']]);
        }

        if (isset($processorConfig['type'])) {
            $service->addMethodCall('setType', [$processorConfig['type']]);
        }

        $processor->setArgument('$service', $service);

        $container->setDefinition('.ecs_logging.processor.service', $processor);
    }

    protected function configureTracingProcessor(array $config, ContainerBuilder $container): void
    {
        if (!isset($config['processor']['tracing']) || !$config['processor']['tracing']['enabled']) {
            return;
        }

        $processorConfig = $config['processor']['tracing'];

        $processor = new Definition(TracingProcessor::class);
        $processor->setArgument('$fieldName', $processorConfig['field_name']);

        $this->configureMonologProcessor($config, $processorConfig, $processor);

        $container->setDefinition('.ecs_logging.processor.tracing', $processor);
    }

    protected function configureUserProcessor(array $config, ContainerBuilder $container): void
    {
        if (!isset($config['processor']['user']) || !$config['processor']['user']['enabled']) {
            return;
        }

        $processorConfig = $config['processor']['user'];

        if ($processorConfig['provider'] ?? null === null) {
            $provider = new Definition(EcsUserProvider::class);
            $provider->setAutowired(true);
        } else {
            $provider = new Reference($processorConfig['provider']);
        }

        $processor = new Definition(UserProcessor::class);
        $processor->setArgument('$provider', $provider);
        $processor->setArgument('$domain', $processorConfig['domain']);

        $this->configureMonologProcessor($config, $processorConfig, $processor);

        $container->setDefinition('.ecs_logging.processor.user', $processor);
    }

    protected function configureMonologProcessor(array $config, array $configOverride, Definition $processor): void
    {
        $channels = !empty($configOverride['channels']) ? $configOverride['channels'] : $config['monolog']['channels'];
        foreach ($channels as $channel) {
            $processor->addTag('monolog.processor', ['channel' => $channel]);
        }

        $handlers = !empty($configOverride['handlers']) ? $configOverride['handlers'] : $config['monolog']['handlers'];
        foreach ($handlers as $handler) {
            $processor->addTag('monolog.processor', ['handler' => $handler]);
        }
    }
}
