<?php

declare(strict_types=1);

namespace TXC\Box\Infrastructure\CompilerPasses\Domain;

use Doctrine\ORM\Mapping as ORM;
use Roave\BetterReflection\Reflection\ReflectionClass;
use TXC\Box\Infrastructure\DependencyInjection\ContainerBuilder;
use TXC\Box\Infrastructure\Environment\Settings;
use TXC\Box\Infrastructure\Resolvers\ClassAttributeResolver;
use TXC\Box\Interfaces\CompilerPass;
use TXC\Box\Interfaces\DomainInterface;

class DomainCompilerPass implements CompilerPass
{
    public function process(ContainerBuilder $container, Settings $settings): void
    {
        if (!is_dir(Settings::getAppRoot() . '/src/Domain')) {
            return;
        }

        $definition = $container->findDefinition(DomainContainer::class);
        foreach ($this->getDomains() as $class) {
            $definition->method('registerDomain', \DI\autowire($class->getName()));
        }
        $container->addDefinitions([DomainContainer::class => $definition]);
    }

    /**
     * @return iterable<ReflectionClass>
     */
    protected function getDomains(): iterable
    {
        $classes = ClassAttributeResolver::resolve(ORM\Entity::class, '/src/Domain');
        foreach ($classes as $class) {
            $reflection = ReflectionClass::createFromName($class);
            if (!$reflection->implementsInterface(DomainInterface::class)) {
                continue;
            }

            yield $reflection;
        }
    }
}
