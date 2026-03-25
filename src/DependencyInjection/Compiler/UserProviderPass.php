<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\DependencyInjection\Compiler;

use Aubes\EcsLoggingBundle\Security\EcsUserProviderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

final class UserProviderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('.ecs_logging.processor.user')) {
            return;
        }

        $definition = $container->getDefinition('.ecs_logging.processor.user');
        $providerArg = $definition->getArgument('$provider');

        if (!$providerArg instanceof Reference) {
            return;
        }

        $providerId = (string) $providerArg;

        if (!$container->hasDefinition($providerId) && !$container->hasAlias($providerId)) {
            throw new InvalidArgumentException(\sprintf('The service "%s" configured for "ecs_logging.processor.user.provider" does not exist in the container.', $providerId));
        }

        $class = $container->findDefinition($providerId)->getClass();

        if ($class === null || !\is_a($class, EcsUserProviderInterface::class, true)) {
            throw new InvalidArgumentException(\sprintf('The service "%s" configured for "ecs_logging.processor.user.provider" must implement "%s".', $providerId, EcsUserProviderInterface::class));
        }
    }
}
