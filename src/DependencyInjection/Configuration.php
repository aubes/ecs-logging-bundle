<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     *
     * @psalm-suppress UndefinedMethod
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('ecs_logging');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('monolog')
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
                        ->arrayNode('service')
                            ->canBeEnabled()
                            ->children()
                                ->scalarNode('name')->end()
                                ->scalarNode('version')->end()
                                ->scalarNode('ephemeral_id')->end()
                                ->scalarNode('id')->end()
                                ->scalarNode('node_name')->end()
                                ->scalarNode('state')->end()
                                ->scalarNode('type')->end()
                            ->end()
                            ->append($this->addHandlersNode())
                            ->append($this->addChannelsNode())
                        ->end()
                        ->arrayNode('error')
                            ->canBeEnabled()
                            ->children()
                                ->scalarNode('field_name')->defaultValue('error')->end()
                            ->end()
                            ->append($this->addHandlersNode())
                            ->append($this->addChannelsNode())
                        ->end()
                        ->arrayNode('tracing')
                            ->canBeEnabled()
                            ->children()
                                ->scalarNode('field_name')->defaultValue('tracing')->end()
                            ->end()
                            ->append($this->addHandlersNode())
                            ->append($this->addChannelsNode())
                        ->end()
                        ->arrayNode('user')
                            ->canBeEnabled()
                            ->children()
                                ->scalarNode('domain')->defaultNull()->end()
                                ->scalarNode('provider')->defaultNull()->end()
                            ->end()
                            ->append($this->addHandlersNode())
                            ->append($this->addChannelsNode())
                        ->end()
                        ->arrayNode('auto_label')
                            ->canBeEnabled()
                            ->children()
                                ->arrayNode('fields')
                                    ->scalarPrototype()->end()
                                ->end()
                            ->end()
                            ->append($this->addHandlersNode())
                            ->append($this->addChannelsNode())
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }

    /**
     * @psalm-suppress UndefinedMethod
     */
    public function addHandlersNode(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('handlers');

        return $treeBuilder->getRootNode()
            ->info('Logging handler list the processor should be pushed to')
            ->scalarPrototype()->end()
        ;
    }

    /**
     * @psalm-suppress UndefinedMethod
     */
    public function addChannelsNode(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('channels');

        return $treeBuilder->getRootNode()
            ->info('Logging channel list the processor should be pushed to')
            ->scalarPrototype()->end()
        ;
    }
}
