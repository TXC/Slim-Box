<?php

declare(strict_types=1);

namespace TXC\Box\Infrastructure\CompilerPasses\Domain;

use Doctrine\ORM\Mapping as ORM;
use Roave\BetterReflection\Reflection\ReflectionClass;
use TXC\Box\Infrastructure\DependencyInjection\ContainerBuilder;
use TXC\Box\Infrastructure\Environment\Settings;
use TXC\Box\Interfaces\CompilerPass;
use TXC\Box\Interfaces\DomainInterface;

class DomainCompilerPass implements CompilerPass
{
    public function process(ContainerBuilder $container, Settings $settings): void
    {
        if (!is_dir(Settings::getAppRoot() . '/src/Domain')) {
            return;
        }

        $blacklist = $settings->get('blacklist.compilerpass.domain');
        $definition = $container->findDefinition(DomainContainer::class);
        foreach ($this->getDomains($container, $blacklist) as $class) {
            if (in_array($class, $blacklist)) {
                continue;
            }
            $definition->method('registerDomain', \DI\autowire($class->getName()));
        }
        $container->addDefinitions([DomainContainer::class => $definition]);
    }

    /**
     * @param ContainerBuilder $container
     * @param array $blacklist
     * @return \Generator<ReflectionClass>
     */
    protected function getDomains(ContainerBuilder $container, array $blacklist): \Generator
    {
        $classes = $container->findTaggedWithClassAttribute(ORM\Entity::class, '/src/Domain');
        foreach ($classes as $class) {
            if (in_array($class, $blacklist)) {
                continue;
            }

            $reflection = ReflectionClass::createFromName($class);
            if (!$reflection->implementsInterface(DomainInterface::class)) {
                continue;
            }

            yield $reflection;
        }
    }
}
