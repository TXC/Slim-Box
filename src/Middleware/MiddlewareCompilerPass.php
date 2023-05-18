<?php

declare(strict_types=1);

namespace TXC\Box\Middleware;

use TXC\Box\DependencyInjection\ContainerBuilder;
use TXC\Box\Environment\Settings;
use TXC\Box\Interface\CompilerPass;
use Psr\Http\Server\MiddlewareInterface;

use function DI\autowire;

class MiddlewareCompilerPass implements CompilerPass
{
    public function process(ContainerBuilder $container): void
    {
        return;
        if (!is_dir(Settings::getAppRoot() . '/src/Application/Middleware')) {
            return;
        }
        $definition = $container->findDefinition(MiddlewareContainer::class);
        $classes = $container->findClassesThatImplements(MiddlewareInterface::class, '/src/Application/Middleware');
        foreach ($classes as $class) {
            $definition->method('registerMiddleware', autowire($class));
        }

        $container->addDefinitions(
            [MiddlewareContainer::class => $definition],
        );
    }
}
