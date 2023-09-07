<?php

declare(strict_types=1);

namespace TXC\Box\Infrastructure\CompilerPasses\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use TXC\Box\Infrastructure\DependencyInjection\ContainerBuilder;
use TXC\Box\Infrastructure\Environment\Settings;
use TXC\Box\Infrastructure\Resolvers\InterfaceResolver;
use TXC\Box\Interfaces\CompilerPass;

use function DI\autowire;

class MiddlewareCompilerPass implements CompilerPass
{
    public function process(ContainerBuilder $container, Settings $settings): void
    {
        $searchDirectory = [
            'src/Application/Middlewares',
            'vendor/txc/slim-box/src/Middlewares',
        ];

        $allowedMiddleware = $settings->get('passes.middleware');
        $definition = $container->findDefinition(MiddlewareContainer::class);
        $classes = InterfaceResolver::resolve(MiddlewareInterface::class, ...$searchDirectory);
        foreach ($classes as $class) {
            if (!in_array($class, $allowedMiddleware)) {
                continue;
            }
            $definition->method('registerMiddleware', autowire($class));
        }

        $container->addDefinitions(
            [MiddlewareContainer::class => $definition],
        );
    }
}
