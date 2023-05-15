<?php

declare(strict_types=1);

namespace TXC\Box\Repository;

use TXC\Box\DependencyInjection\ContainerBuilder;
use TXC\Box\Environment\Settings;
use TXC\Box\Interface\CompilerPass;
use Doctrine\ORM\Mapping as ORM;

class RepositoryCompilerPass implements CompilerPass
{
    public function process(ContainerBuilder $container): void
    {
        if (!is_dir(Settings::getAppRoot() . 'src/Domain')) {
            return;
        }

        $definition = $container->findDefinition(RepositoryContainer::class);
        foreach ($container->findTaggedWithClassAttribute(ORM\Entity::class, 'src/Domain') as $class) {
            $reflection = new \ReflectionClass($class);
            $attributes = $reflection->getAttributes(ORM\Entity::class);
            foreach ($attributes as $attribute) {
                $arguments = $attribute->getArguments();
                if (!isset($arguments['repositoryClass'])) {
                    continue;
                }
                $repositoryClass = $arguments['repositoryClass'];
            }
            $definition->method('registerCommand', \DI\autowire($class));
        }

        $container->addDefinitions(
            [RepositoryContainer::class => $definition],
        );
    }
}
