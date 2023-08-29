<?php

declare(strict_types=1);

namespace TXC\Box\Infrastructure\CompilerPasses\Routes;

use DI\Definition\Helper\AutowireDefinitionHelper;
use TXC\Box\Attributes\Route\GroupRoute;
use TXC\Box\Attributes\Route\IsRoutable;
use TXC\Box\Attributes\Route\Route;
use TXC\Box\Infrastructure\DependencyInjection\ContainerBuilder;
use TXC\Box\Infrastructure\Environment\Settings;
use TXC\Box\Infrastructure\Resolvers\ClassAttributeResolver;
use TXC\Box\Interfaces\CompilerPass;

use function DI\autowire;

class RoutesCompilerPass implements CompilerPass
{
    private AutowireDefinitionHelper $definition;

    public function process(ContainerBuilder $container, Settings $settings): void
    {
        if (!is_dir(Settings::getAppRoot() . '/src/Application')) {
            return;
        }
        $this->definition = $container->findDefinition(RoutesContainer::class);
        $classes = ClassAttributeResolver::resolve(IsRoutable::class, '/src/Application');
        $routes = [];
        foreach ($classes as $class) {
            $reflection = new \ReflectionClass($class);
            $this->collectClassRoutes($reflection, $routes);
        }

        $container->addDefinitions([RoutesContainer::class => $this->definition]);
    }

    private function collectClassRoutes(\ReflectionClass $reflection, array &$routes): void
    {
        foreach ($reflection->getAttributes(GroupRoute::class) as $classAttribute) {
            //** @var GroupRoute $groupRoute */
            //$groupRoute = $classAttribute->newInstance();
            $groupRoute = autowire(GroupRoute::class);
            foreach ($classAttribute->getArguments() as $param => $value) {
                $groupRoute->constructorParameter($param, $value);
            }
        }
        $routes = [];
        foreach ($reflection->getMethods() as $reflectionMethod) {
            foreach ($reflectionMethod->getAttributes(Route::class) as $attribute) {
                ///** @var Route $route */
                //$route = $attribute->newInstance();
                $route = autowire(Route::class);
                foreach ($attribute->getArguments() as $param => $value) {
                    $route->constructorParameter($param, $value);
                }
                //$route->setCallable([$reflection->getName(), $reflectionMethod->getName()]);
                $callable = autowire($reflection->getName())
                            ->method($reflectionMethod->getName());

                $route->methodParameter(
                    'setCallable',
                    0,
                    $callable
                    //[$reflection->getName(), $reflectionMethod->getName()]
                );
                $routes[] = $route;
            }
        }
        if (isset($groupRoute)) {
            //$groupRoute->addRoutes(...$routes);
            $groupRoute->methodParameter('addRoutes', 0, ...$routes);
            $this->definition->method('registerGroupRoute', $groupRoute);
        } else {
            foreach ($routes as $route) {
                $this->definition->method('registerRoute', $route);
            }
        }
    }
}
