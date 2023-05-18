<?php

declare(strict_types=1);

namespace TXC\Box\Console;

use TXC\Box\DependencyInjection\ContainerBuilder;
use TXC\Box\Environment\Settings;
use TXC\Box\Interface\CompilerPass;
use Composer\InstalledVersions;
use DI\Definition\Helper\AutowireDefinitionHelper;
use Doctrine\DBAL\Tools\Console as DBALConsole;
use Doctrine\Migrations as Migrations;
use Doctrine\Migrations\Tools\Console\Command as MigrationCommand;
use Doctrine\ORM;
use Doctrine\ORM\Tools\Console\Command as ORMCommand;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;
use Symfony\Component\Console\Attribute\AsCommand;

use function DI\autowire;

class ConsoleCommandCompilerPass implements CompilerPass
{
    public function process(ContainerBuilder $container): void
    {
        $directoryRestriction = ['src/Commands/'];

        if (is_dir(Settings::getAppRoot() . '/vendor/txc/slim-box')) {
            $directoryRestriction[] = '/vendor/txc/slim-box/';
        }

        $definition = $container->findDefinition(ConsoleCommandContainer::class);
        $classes = $container->findTaggedWithClassAttribute(AsCommand::class, ...$directoryRestriction);
        foreach ($classes as $class) {
            $definition->method('registerCommand', autowire($class));
        }

        $this->addDoctrineCommands($definition);

        $container->addDefinitions(
            [ConsoleCommandContainer::class => $definition],
        );
    }

    protected function addDoctrineCommands(AutowireDefinitionHelper $definition): void
    {
        $dependencyFactory = \DI\factory(function (
            \TXC\Box\Environment\Settings $settings,
            ORM\EntityManager $entityManager
        ) {
            return Migrations\DependencyFactory::fromEntityManager(
                new Migrations\Configuration\Migration\ConfigurationArray($settings->get('doctrine.migrations')),
                new Migrations\Configuration\EntityManager\ExistingEntityManager($entityManager)
            );
        });
        $entityManagerProvider = \DI\factory(
            function (\Doctrine\ORM\EntityManager $entityManager): SingleManagerProvider {
                return new SingleManagerProvider($entityManager);
            }
        );

        $doctrineClasses = array_merge(
            [],
            self::getDoctrineDbalCommands(),
            self::getDoctrineORMCommands(),
            self::getDoctrineMigrationCommands()
        );
        foreach ($doctrineClasses as $class) {
            if (is_subclass_of($class, MigrationCommand\DoctrineCommand::class)) {
                $class = autowire($class)
                    ->constructorParameter(0, $dependencyFactory)
                    ->constructorParameter(1, null);
            } elseif (is_subclass_of($class, ORMCommand\AbstractEntityManagerCommand::class)) {
                $class = autowire($class)
                    ->constructorParameter(0, $entityManagerProvider);
            } else {
                $class = autowire($class);
            }
            $definition->method('registerCommand', $class);
        }
    }

    protected function getDoctrineDbalCommands(): array
    {
        if (!InstalledVersions::isInstalled('doctrine/dbal')) {
            return [];
        }
        return [
            // DBAL Commands
            DBALConsole\Command\ReservedWordsCommand::class,
            DBALConsole\Command\RunSqlCommand::class,
        ];
    }

    protected function getDoctrineORMCommands(): array
    {
        if (!InstalledVersions::isInstalled('doctrine/orm')) {
            return [];
        }
        return [
            // ORM Commands
            ORMCommand\ClearCache\CollectionRegionCommand::class,
            ORMCommand\ClearCache\EntityRegionCommand::class,
            ORMCommand\ClearCache\MetadataCommand::class,
            ORMCommand\ClearCache\QueryCommand::class,
            ORMCommand\ClearCache\QueryRegionCommand::class,
            ORMCommand\ClearCache\ResultCommand::class,
            ORMCommand\SchemaTool\CreateCommand::class,
            ORMCommand\SchemaTool\UpdateCommand::class,
            ORMCommand\SchemaTool\DropCommand::class,
            ORMCommand\EnsureProductionSettingsCommand::class,
            ORMCommand\ConvertDoctrine1SchemaCommand::class,
            ORMCommand\GenerateRepositoriesCommand::class,
            ORMCommand\GenerateEntitiesCommand::class,
            ORMCommand\GenerateProxiesCommand::class,
            ORMCommand\ConvertMappingCommand::class,
            ORMCommand\RunDqlCommand::class,
            ORMCommand\ValidateSchemaCommand::class,
            ORMCommand\InfoCommand::class,
            ORMCommand\MappingDescribeCommand::class,
        ];
    }

    protected function getDoctrineMigrationCommands(): array
    {
        if (!InstalledVersions::isInstalled('doctrine/migrations')) {
            return [];
        }
        return [
            MigrationCommand\CurrentCommand::class,
            MigrationCommand\DumpSchemaCommand::class,
            MigrationCommand\ExecuteCommand::class,
            MigrationCommand\GenerateCommand::class,
            MigrationCommand\LatestCommand::class,
            MigrationCommand\MigrateCommand::class,
            MigrationCommand\RollupCommand::class,
            MigrationCommand\StatusCommand::class,
            MigrationCommand\VersionCommand::class,
            MigrationCommand\UpToDateCommand::class,
            MigrationCommand\SyncMetadataCommand::class,
            MigrationCommand\ListCommand::class,
            MigrationCommand\DiffCommand::class,
        ];
    }
}
