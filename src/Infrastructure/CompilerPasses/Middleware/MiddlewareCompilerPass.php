<?php

declare(strict_types=1);

namespace TXC\Box\Infrastructure\CompilerPasses\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use TXC\Box\Infrastructure\DependencyInjection\ContainerBuilder;
use TXC\Box\Infrastructure\Environment\Settings;
use TXC\Box\Interfaces\CompilerPass;

use function DI\autowire;

class MiddlewareCompilerPass implements CompilerPass
{
    public function process(ContainerBuilder $container, Settings $settings): void
    {
        if (!is_dir(Settings::getAppRoot() . '/src/Application/Middleware')) {
            return;
        }
        $blacklist = $settings->get('blacklist.compilerpass.middleware');
        $definition = $container->findDefinition(MiddlewareContainer::class);
        $classes = $container->findClassesThatImplements(MiddlewareInterface::class, '/src/Application/Middleware');
        foreach ($classes as $class) {
            if (in_array($class, $blacklist)) {
                continue;
            }
            $definition->method('registerMiddleware', autowire($class));
        }

        $container->addDefinitions(
            [MiddlewareContainer::class => $definition],
        );
    }
}
