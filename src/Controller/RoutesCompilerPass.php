<?php

declare(strict_types=1);

namespace TXC\Box\Controller;

use TXC\Box\Attribute\Route\GroupRoute;
use TXC\Box\Attribute\Route\IsRoutable;
use TXC\Box\Attribute\Route\Route;
use TXC\Box\DependencyInjection\ContainerBuilder;
use TXC\Box\Environment\Settings;
use TXC\Box\Interface\CompilerPass;
use DI\Definition\Helper\AutowireDefinitionHelper;

use function DI\autowire;

class RoutesCompilerPass implements CompilerPass
{
    private AutowireDefinitionHelper $definition;
    private ContainerBuilder $container;

    public function process(ContainerBuilder $container): void
    {
        if (!is_dir(Settings::getAppRoot() . '/src/Application')) {
            return;
        }
        $this->container = $container;
        $this->definition = $this->container->findDefinition(RoutesContainer::class);
        $classes = $this->container->findTaggedWithClassAttribute(IsRoutable::class, '/src/Application');
        $routes = [];
        foreach ($classes as $class) {
            $reflection = new \ReflectionClass($class);
            $this->collectClassRoutes($reflection, $routes);
        }

        //$this->registerRoutes($routes);
        $this->container->addDefinitions([RoutesContainer::class => $this->definition]);
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
