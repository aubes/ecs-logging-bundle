<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class ProcessorConfigurationBuilder
{
    public function addServiceProcessorNode(): ArrayNodeDefinition
    {
        $node = (new TreeBuilder('service'))->getRootNode();
        $node
            ->canBeEnabled()
            ->info('Stamps service.* ECS fields on every log record (static values set at boot time).')
            ->children()
                ->scalarNode('name')->info('service.name — logical name of the service (e.g. "my-api").')->end()
                ->scalarNode('address')->info('service.address — address at which the service can be reached (e.g. "https://api.example.com").')->end()
                ->scalarNode('version')->info('service.version — version of the service (e.g. "1.0.0").')->end()
                ->scalarNode('ephemeral_id')->info('service.ephemeral_id — ephemeral ID of the service instance; changes on restart.')->end()
                ->scalarNode('id')->info('service.id — unique identifier of the service instance (e.g. container ID).')->end()
                ->scalarNode('node_name')->info('service.node.name — name of the node running this service instance.')->end()
                ->scalarNode('state')->info('service.state — current lifecycle state (e.g. "started", "deployed").')->end()
                ->scalarNode('type')->info('service.type — type of service (e.g. "web", "worker", "console").')->end()
            ->end()
            ->append($this->addHandlersNode())
            ->append($this->addChannelsNode());

        return $node;
    }

    public function addErrorProcessorNode(): ArrayNodeDefinition
    {
        $node = (new TreeBuilder('error'))->getRootNode();
        $node
            ->canBeEnabled()
            ->children()
                ->scalarNode('field_name')->defaultValue('error')->info('Context key read by the processor (e.g. $logger->error("msg", ["error" => $e])).')->end()
                ->booleanNode('map_exception_key')
                    ->defaultFalse()
                    ->info('Also process context[\'exception\'] (Symfony convention) and map it to error.*')
                ->end()

            ->end()
            ->append($this->addHandlersNode())
            ->append($this->addChannelsNode());

        return $node;
    }

    public function addTracingProcessorNode(): ArrayNodeDefinition
    {
        $node = (new TreeBuilder('tracing'))->getRootNode();
        $node
            ->canBeEnabled()
            ->children()
                ->enumNode('mode')
                    ->values(['default', 'opentelemetry'])
                    ->defaultValue('default')
                    ->info('"default" reads a nested array from context[field_name]. "opentelemetry" reads flat trace_id/span_id keys injected by the OTel Monolog processor (field_name is ignored).')
                ->end()
                ->scalarNode('field_name')->defaultValue('tracing')->info('Context key read by the processor in "default" mode. Ignored in "opentelemetry" mode.')->end()
            ->end()
            ->append($this->addHandlersNode())
            ->append($this->addChannelsNode());

        return $node;
    }

    public function addUserProcessorNode(): ArrayNodeDefinition
    {
        $node = (new TreeBuilder('user'))->getRootNode();
        $node
            ->canBeEnabled()
            ->info('Injects the authenticated user as ECS user.* fields. WARNING: user.name is populated from getUserIdentifier(), which is typically a PII value (e.g. email address). Ensure your data-retention and privacy policies cover this field before enabling.')
            ->children()
                ->scalarNode('domain')->defaultNull()->info('Default user.domain stamped on every log record (e.g. "in_memory", "database"). Can be overridden at runtime by the provider\'s getDomain().')->end()
                ->scalarNode('provider')->defaultNull()->info('Service ID of a custom EcsUserProviderInterface implementation. Defaults to the built-in provider backed by Symfony Security.')->end()
            ->end()
            ->append($this->addHandlersNode())
            ->append($this->addChannelsNode());

        return $node;
    }

    public function addAutoLabelProcessorNode(): ArrayNodeDefinition
    {
        $node = (new TreeBuilder('auto_label'))->getRootNode();
        $node
            ->canBeEnabled()
            ->children()
                ->enumNode('mode')
                    ->values(['bundle', 'full', 'custom'])
                    ->defaultValue('bundle')
                    ->info('ECS field whitelist: "bundle" (fields covered by this bundle), "full" (all ECS field sets), "custom" (only fields listed in "fields").')
                ->end()
                ->arrayNode('fields')
                    ->info('Extra ECS field names to whitelist when mode is "custom". Ignored for other modes.')
                    ->scalarPrototype()->end()
                ->end()
                ->booleanNode('move_to_labels')
                    ->defaultFalse()
                    ->info('Move non-ECS context fields into labels instead of dropping them.')
                ->end()
                ->booleanNode('include_extra')
                    ->defaultFalse()
                    ->info('Also process non-ECS keys from Monolog extra (e.g. from ProcessIdProcessor, HostnameProcessor).')
                ->end()
                ->enumNode('non_scalar_strategy')
                    ->values(['skip', 'json'])
                    ->defaultValue('skip')
                    ->info('Strategy for non-ECS context values that are not scalar. "skip" removes them silently; "json" converts them via json_encode (falls back to skip on failure).')
                ->end()
            ->end()
            ->append($this->addHandlersNode())
            ->append($this->addChannelsNode());

        return $node;
    }

    public function addHostProcessorNode(): ArrayNodeDefinition
    {
        $node = (new TreeBuilder('host'))->getRootNode();
        $node
            ->canBeEnabled()
            ->children()
                ->scalarNode('name')
                    ->defaultNull()
                    ->info('host.name — auto-detected via gethostname() if null')
                ->end()
                ->arrayNode('ip')
                    ->info('host.ip — auto-detected via gethostbyname() if empty and resolve_ip is true')
                    ->scalarPrototype()->end()
                ->end()
                ->booleanNode('resolve_ip')
                    ->defaultFalse()
                    ->info('Resolve host.ip via gethostbyname() when ip is not provided. WARNING: this is a blocking DNS call at container warm-up time.')
                ->end()
                ->scalarNode('architecture')
                    ->defaultNull()
                    ->info('host.architecture — auto-detected via php_uname(\'m\') if null')
                ->end()
            ->end()
            ->append($this->addHandlersNode())
            ->append($this->addChannelsNode());

        return $node;
    }

    public function addHttpRequestProcessorNode(): ArrayNodeDefinition
    {
        $node = (new TreeBuilder('http_request'))->getRootNode();
        $node
            ->canBeEnabled()
            ->children()
                ->booleanNode('include_full_url')
                    ->defaultFalse()
                    ->info('Log url.full and url.query. WARNING: may expose sensitive data (tokens, API keys) present in query parameters.')
                ->end()
                ->booleanNode('include_client_ip')
                    ->defaultFalse()
                    ->info('Log client.ip from the request. Uses Symfony trusted proxies to resolve the real IP behind load balancers.')
                ->end()
                ->booleanNode('include_referrer')
                    ->defaultFalse()
                    ->info('Log http.request.referrer. WARNING: the Referer header may contain external URLs with sensitive data (tokens, session identifiers).')
                ->end()
            ->end()
            ->append($this->addHandlersNode())
            ->append($this->addChannelsNode());

        return $node;
    }

    public function addCorrelationIdProcessorNode(): ArrayNodeDefinition
    {
        $node = (new TreeBuilder('correlation_id'))->getRootNode();
        $node
            ->canBeEnabled()
            ->info('Injects a correlation ID from Monolog extra into the log context (works with any library that populates extra).')
            ->children()
                ->scalarNode('field_name')
                    ->defaultValue('correlation_id')
                    ->info('Key to read from Monolog extra (e.g. "correlation_id"). Must match the field name configured in the library that populates extra.')
                ->end()
                ->enumNode('target')
                    ->values(['labels', 'trace'])
                    ->defaultValue('labels')
                    ->info('Where to write the correlation ID: "labels" writes to labels.correlation_id, "trace" writes to trace.id. WARNING: ECS expects trace.id to be a 32-character hex string; ensure your correlation ID generator produces this format when using "trace".')
                ->end()
            ->end()
            ->append($this->addHandlersNode())
            ->append($this->addChannelsNode());

        return $node;
    }

    public function addHandlersNode(): NodeDefinition
    {
        return (new TreeBuilder('handlers'))->getRootNode()
            ->info('Logging handler list the processor should be pushed to')
            ->scalarPrototype()->end()
        ;
    }

    public function addChannelsNode(): NodeDefinition
    {
        return (new TreeBuilder('channels'))->getRootNode()
            ->info('Logging channel list the processor should be pushed to')
            ->scalarPrototype()->end()
        ;
    }
}
