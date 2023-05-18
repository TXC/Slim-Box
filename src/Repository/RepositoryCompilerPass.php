<?php

declare(strict_types=1);

namespace TXC\Box\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Roave\BetterReflection\Reflection\ReflectionClass;
use TXC\Box\DependencyInjection\ContainerBuilder;
use TXC\Box\Domain\DomainCompilerPass;
use TXC\Box\Environment\Settings;
use TXC\Box\Interface\CompilerPass;
use Doctrine\ORM\Mapping as ORM;

class RepositoryCompilerPass extends DomainCompilerPass implements CompilerPass
{
    public function process(ContainerBuilder $container): void
    {
        if (!is_dir(Settings::getAppRoot() . '/src/Domain')) {
            return;
        }

        $definition = $container->findDefinition(RepositoryContainer::class);
        foreach ($this->getDomains($container) as $domain) {
            foreach ($domain->getAttributesByName(ORM\Entity::class) as $classAttribute) {
                foreach ($classAttribute->getArguments() as $param => $value) {
                    if ($param != 'repositoryClass') {
                        continue;
                    }
                    $repository = \DI\autowire($value)
                        ->constructorParameter(0, \DI\get(EntityManagerInterface::class))
                        ->constructorParameter(1, $domain->getName());

                    $definition->method('registerRepository', $repository);
                }
            }
        }
        $container->addDefinitions([RepositoryContainer::class => $definition]);
    }
}
