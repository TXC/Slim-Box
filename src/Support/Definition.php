<?php

declare(strict_types=1);

namespace TXC\Box\Support;

use TXC\Box\Console\ConsoleCommandContainer;
use TXC\Box\Controller\RoutesContainer;
use TXC\Box\Domain\DomainContainer;
use TXC\Box\Environment\Environment;
use TXC\Box\Environment\Settings;
use TXC\Box\Interface\DomainInterface;
use TXC\Box\Interface\RepositoryInterface;
use TXC\Box\Repository\RepositoryContainer;
use Composer\InstalledVersions;
use DI\Factory\RequestedEntry;
use Doctrine\DBAL;
use Doctrine\Migrations\Configuration as MigrationConfiguration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\ORM;
use Doctrine\ORM\Tools\Console as ORMConsole;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Log\LoggerInterface;
use TXC\Box\Interface\RestInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Console\Application;

use function DI\get;

class Definition
{
    public static function collect(): array
    {
        $packages = [];
        if (InstalledVersions::isInstalled('monolog/monolog')) {
            $packages = array_merge($packages, self::addMonolog());
        }
        if (InstalledVersions::isInstalled('doctrine/dbal')) {
            $packages = array_merge($packages, self::addDoctrineDbal());
        }
        if (InstalledVersions::isInstalled('doctrine/orm')) {
            $packages = array_merge($packages, self::addDoctrineOrm());
        }
        if (InstalledVersions::isInstalled('doctrine/migrations')) {
            $packages = array_merge($packages, self::addDoctrineMigrations());
        }
        if (InstalledVersions::isInstalled('symfony/console')) {
            $packages = array_merge($packages, self::addSymfonyConsole());
        }

        return array_merge($packages, self::addSkeleton());
    }

    protected static function addMonolog(): array
    {
        return [
            Logger::class => function (Settings $settings): Logger {
                $name = $settings->get('logger.prefix');

                $logger = new Logger($name);

                $fileHandler = new StreamHandler(
                    $settings->get('logger.path') . '/' . $name . '.log',
                    $settings->get('logger.level')
                );

                $fileHandler->setFormatter(new \Monolog\Formatter\LineFormatter());
                $logger->pushHandler($fileHandler);

                return $logger;
            },
            LoggerInterface::class => get(Logger::class),
        ];
    }

    protected static function addDoctrineOrm(): array
    {
        return [
            ORM\Configuration::class => function (Settings $settings, Logger $logger): ORM\Configuration {
                // Use the ArrayAdapter or the FilesystemAdapter depending on the value of the 'dev_mode' setting
                // You can substitute the FilesystemAdapter for any other cache you prefer from the cache library
                $cache = $settings->get('doctrine.dev_mode') ?
                    new ArrayAdapter() :
                    new FilesystemAdapter(directory: $settings->get('doctrine.cache_dir'));

                $configuration = ORM\ORMSetup::createAttributeMetadataConfiguration(
                    $settings->get('doctrine.metadata_dirs'),
                    $settings->get('doctrine.dev_mode'),
                    '',
                    $cache
                );

                $middlewares = [];
                if ($settings->get('doctrine.dev_mode') === true) {
                    $name = 'doctrine';
                    $dbalLogger = $logger->withName($name);
                    $fileHandler = new StreamHandler(
                        $settings->get('logger.path') . '/' . $name . '.log',
                        $settings->get('logger.level')
                    );
                    $dbalLogger->pushHandler($fileHandler);
                    $middlewares[] = new DBAL\Logging\Middleware($dbalLogger);
                }
                $configuration->setMiddlewares($middlewares);

                $configuration->setNamingStrategy(new ORM\Mapping\UnderscoreNamingStrategy(CASE_LOWER, true));
                return $configuration;
            },
            ORM\EntityManager::class => function (
                DBAL\Connection $connection,
                ORM\Configuration $configuration
            ): ORM\EntityManager {
                if (
                    InstalledVersions::isInstalled('darsyn/ip')
                    && class_exists(\Darsyn\IP\Doctrine\MultiType::class)
                ) {
                    DBAL\Types\Type::addType('ip', \Darsyn\IP\Doctrine\MultiType::class);
                }

                return new ORM\EntityManager($connection, $configuration);
            },
            ORM\EntityManagerInterface::class => \DI\get(ORM\EntityManager::class),
            ORMConsole\EntityManagerProvider::class => function (
                ORM\EntityManager $entityManager
            ): ORMConsole\EntityManagerProvider\SingleManagerProvider {
                return new ORMConsole\EntityManagerProvider\SingleManagerProvider($entityManager);
            },
        ];
    }

    protected static function addDoctrineDbal(): array
    {
        return [
            DBAL\Connection::class => function (
                Settings $settings,
                ORM\Configuration $configuration
            ): DBAL\Connection {
                return DBAL\DriverManager::getConnection(
                    $settings->get('doctrine.connection'),
                    $configuration
                );
            },
            DBAL\Tools\Console\ConnectionProvider::class => function (
                ORMConsole\EntityManagerProvider $entityManagerProvider
            ) {
                return new ORMConsole\EntityManagerProvider\ConnectionFromManagerProvider($entityManagerProvider);
            },
        ];
    }

    protected static function addDoctrineMigrations(): array
    {
        return [
            DependencyFactory::class => function (
                Settings $settings,
                ORM\EntityManager $entityManager
            ): DependencyFactory {
                return DependencyFactory::fromEntityManager(
                    new MigrationConfiguration\Migration\ConfigurationArray($settings->get('doctrine.migrations')),
                    new MigrationConfiguration\EntityManager\ExistingEntityManager($entityManager)
                );
            },
        ];
    }

    protected static function addSymfonyConsole(): array
    {
        return [
            Application::class => function (ConsoleCommandContainer $consoleCommandContainer) {
                $application = new Application();
                foreach ($consoleCommandContainer->getCommands() as $command) {
                    $application->add($command);
                }
                return $application;
            },
        ];
    }

    protected static function addSkeleton(): array
    {
        return [
            Environment::class => function () {
                return Environment::from($_ENV['ENVIRONMENT']);
            },
            // Settings.
            Settings::class => function (): Settings {
                return Settings::load();
            },
            ContainerInterface::class => \DI\get(\DI\Container::class),
/*
            CallableResolverInterface::class => function (ContainerInterface $container) {
                $callableResolver = new InvokerCallableResolver($container);
                return new CallableResolver($callableResolver);
            },
            ResponseFactoryInterface::class => function () {
                return \Slim\Factory\AppFactory::determineResponseFactory();
            },
            RouteCollectorProxyInterface::class => function (
                ResponseFactoryInterface $responseFactory,
                CallableResolverInterface $callableResolver,
                ContainerInterface $container
            ) {
                return new RouteCollector($responseFactory, $callableResolver, $container);
            },
*/
            DomainInterface::class => function (DomainContainer $domainContainer, RequestedEntry $entry) {
                $name = $entry->getName();
                $domain = $domainContainer->getDomain($name);
                return $domain;
            },
            //RepositoryInterface::class => function (RepositoryContainer $repositoryContainer, RequestedEntry $entry) {
            //    $name = $entry->getName();
            //    $repository = $repositoryContainer->getRepository($name);
            //    return $repository;
            //},
            RestInterface::class => function (RoutesContainer $routesContainer, RequestedEntry $entry) {
                return $routesContainer->getRoute($entry->getName());
            },
            ServerRequestFactoryInterface::class => \DI\get(ServerRequestFactory::class),
        ];
    }
}
