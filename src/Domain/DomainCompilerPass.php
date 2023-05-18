<?php

declare(strict_types=1);

namespace TXC\Box\Domain;

use Roave\BetterReflection\Reflection\ReflectionClass;
use TXC\Box\DependencyInjection\ContainerBuilder;
use TXC\Box\Environment\Settings;
use TXC\Box\Interface\CompilerPass;
use Doctrine\ORM\Mapping as ORM;
use TXC\Box\Interface\DomainInterface;

class DomainCompilerPass implements CompilerPass
{
    public function process(ContainerBuilder $container): void
    {
        if (!is_dir(Settings::getAppRoot() . '/src/Domain')) {
            return;
        }

        $definition = $container->findDefinition(DomainContainer::class);
        foreach ($this->getDomains($container) as $class) {
            $definition->method('registerDomain', \DI\autowire($class->getName()));
        }
        $container->addDefinitions([DomainContainer::class => $definition]);
    }

    /**
     * @param ContainerBuilder $container
     * @return \Generator<ReflectionClass>
     */
    protected function getDomains(ContainerBuilder $container): \Generator
    {
        $classes = $container->findTaggedWithClassAttribute(ORM\Entity::class, '/src/Domain');
        foreach ($classes as $class) {
            $reflection = ReflectionClass::createFromName($class);
            if (!$reflection->implementsInterface(DomainInterface::class)) {
                continue;
            }

            yield $reflection;
        }
    }
}
