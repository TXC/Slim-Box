<?php

declare(strict_types=1);

namespace TXC\Box\Infrastructure\CompilerPasses\EventListeners;

use League\Event\ListenerSubscriber;
use TXC\Box\Infrastructure\DependencyInjection\ContainerBuilder;
use TXC\Box\Infrastructure\Environment\Settings;
use TXC\Box\Infrastructure\Resolvers\InterfaceResolver;
use TXC\Box\Interfaces\CompilerPass;

use function DI\autowire;

class EventListenerCompilerPass implements CompilerPass
{
    public function process(ContainerBuilder $container, Settings $settings): void
    {
        $searchDirectory = [
            'src/Events',
            'vendor/txc/slim-box/src/Event/Subscribers/'
        ];

        $definition = $container->findDefinition(EventListenerContainer::class);
        $classes = InterfaceResolver::resolve(ListenerSubscriber::class, ...$searchDirectory);
        foreach ($classes as $class) {
            $definition->method('registerListener', autowire($class));
        }

        $container->addDefinitions(
            [EventListenerContainer::class => $definition],
        );
    }
}
