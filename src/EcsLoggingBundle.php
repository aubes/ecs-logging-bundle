<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle;

use Aubes\EcsLoggingBundle\DependencyInjection\Compiler\UserProviderPass;
use Aubes\EcsLoggingBundle\DependencyInjection\ProcessorConfigurationBuilder;
use Aubes\EcsLoggingBundle\DependencyInjection\ProcessorLoader;
use Aubes\EcsLoggingBundle\Formatter\EcsFormatter;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class EcsLoggingBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $definition->rootNode();
        $this->buildRootNode($rootNode);
    }

    private function buildRootNode(ArrayNodeDefinition $rootNode): void
    {
        $config = new ProcessorConfigurationBuilder();

        $rootNode
            ->children()
                ->scalarNode('ecs_version')
                    ->defaultValue('9.3.0')
                    ->info('ECS version declared in the ecs.version field of every log record. Override to match your Elastic Stack version (e.g. "8.11.0").')
                    ->validate()
                        ->ifTrue(static fn (mixed $v): bool => !\is_string($v) || !\preg_match('/^\d+\.\d+\.\d+$/', $v))
                        ->thenInvalid('ecs_version must be a valid semver string (e.g. "9.3.0"), got %s.')
                    ->end()
                ->end()
                ->arrayNode('tags')
                    ->info('Static tags added to every log record (ECS tags field). Values must be non-empty strings.')
                    ->validate()
                        ->ifTrue(static function (mixed $tags): bool {
                            foreach ($tags as $tag) {
                                if (!\is_string($tag) || $tag === '') {
                                    return true;
                                }
                            }

                            return false;
                        })
                        ->thenInvalid('ecs_logging.tags must contain only non-empty strings.')
                    ->end()
                    ->scalarPrototype()->end()
                ->end()
                ->arrayNode('monolog')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('channels')
                            ->info('Default logging channel list the processors should be pushed to')
                            ->scalarPrototype()->end()
                        ->end()
                        ->arrayNode('handlers')
                            ->info('Default logging handler list the processors should be pushed to')
                            ->scalarPrototype()->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('processor')
                    ->children()
                        ->append($config->addServiceProcessorNode())
                        ->append($config->addErrorProcessorNode())
                        ->append($config->addTracingProcessorNode())
                        ->append($config->addUserProcessorNode())
                        ->append($config->addAutoLabelProcessorNode())
                        ->append($config->addHostProcessorNode())
                        ->append($config->addHttpRequestProcessorNode())
                    ->end()
                ->end()
            ->end()
        ;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->register('monolog.formatter.ecs', EcsFormatter::class);

        $builder->getDefinition('monolog.formatter.ecs')
            ->setArgument('$ecsVersion', $config['ecs_version'])
            ->setArgument('$tags', $config['tags']);

        $loader = new ProcessorLoader();
        $loader->registerAutoLabelProcessor($config, $builder);
        $loader->registerErrorProcessor($config, $builder);
        $loader->registerHostProcessor($config, $builder);
        $loader->registerHttpRequestProcessor($config, $builder);
        $loader->registerServiceProcessor($config, $builder);
        $loader->registerTracingProcessor($config, $builder);
        $loader->registerUserProcessor($config, $builder);
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new UserProviderPass());
    }
}
