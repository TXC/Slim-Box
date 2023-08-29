<?php

declare(strict_types=1);

namespace TXC\Box\Infrastructure\CompilerPasses\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;
use TXC\Box\Infrastructure\CompilerPasses\Domain\DomainCompilerPass;
use TXC\Box\Infrastructure\DependencyInjection\ContainerBuilder;
use TXC\Box\Infrastructure\Environment\Settings;
use TXC\Box\Interfaces\CompilerPass;

class RepositoryCompilerPass extends DomainCompilerPass implements CompilerPass
{
    public function process(ContainerBuilder $container, Settings $settings): void
    {
        if (!is_dir(Settings::getAppRoot() . '/src/Domain')) {
            return;
        }

        $definition = $container->findDefinition(RepositoryContainer::class);
        foreach ($this->getDomains() as $domain) {
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
