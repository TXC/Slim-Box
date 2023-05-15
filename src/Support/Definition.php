<?php

declare(strict_types=1);

namespace TXC\Box\Support;

use TXC\Box\Console\ConsoleCommandContainer;
use TXC\Box\Controller\RoutesContainer;
use TXC\Box\Environment\Environment;
use TXC\Box\Environment\Settings;
use TXC\Box\Interface\RepositoryInterface;
use TXC\Box\Repository\RepositoryContainer;
use Composer\InstalledVersions;
use DI\Bridge\Slim\CallableResolver;
use DI\Factory\RequestedEntry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\Console\ConnectionProvider;
use Doctrine\DBAL\Types\Type;
use Doctrine\Migrations\Configuration as MigrationConfiguration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\Console\EntityManagerProvider;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;
use Dotenv\Dotenv;
use Invoker\CallableResolver as InvokerCallableResolver;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Log\LoggerInterface;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;
use TXC\Box\Interface\RestInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Routing\RouteCollector;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Console\Application;
use function DI\get;

class Definition
{
    public static function collect()
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
                $logger = new Logger($settings->get('logger.prefix'));

                $fileHandler = new StreamHandler(
                    $settings->get('logger.path'),
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
            Configuration::class => function (Settings $settings, Logger $logger): Configuration {
                // Use the ArrayAdapter or the FilesystemAdapter depending on the value of the 'dev_mode' setting
                // You can substitute the FilesystemAdapter for any other cache you prefer from the cache library
                $cache = $settings->get('doctrine.dev_mode') ?
                    new ArrayAdapter() :
                    new FilesystemAdapter(directory: $settings->get('doctrine.cache_dir'));

                $configuration = ORMSetup::createAttributeMetadataConfiguration(
                    $settings->get('doctrine.metadata_dirs'),
                    $settings->get('doctrine.dev_mode'),
                    '',
                    $cache
                );

                $middlewares = [];
                if ($settings->get('doctrine.dev_mode') === true) {
                    $middlewares[] = new \Doctrine\DBAL\Logging\Middleware($logger);
                }
                $configuration->setMiddlewares($middlewares);

                $configuration->setNamingStrategy(new \Doctrine\ORM\Mapping\UnderscoreNamingStrategy(CASE_LOWER, true));
                return $configuration;
            },
            EntityManager::class => function (Connection $connection, Configuration $configuration): EntityManager {
                if (
                    InstalledVersions::isInstalled('darsyn/ip')
                    && class_exists(\Darsyn\IP\Doctrine\MultiType::class)
                ) {
                    Type::addType('ip', \Darsyn\IP\Doctrine\MultiType::class);
                }

                return new EntityManager($connection, $configuration);
            },
            EntityManagerInterface::class => \DI\get(EntityManager::class),
            EntityManagerProvider::class => function (EntityManager $entityManager): SingleManagerProvider {
                return new SingleManagerProvider($entityManager);
            },
        ];
    }

    protected static function addDoctrineDbal(): array
    {
        return [
            Connection::class => function (Settings $settings, Configuration $configuration): Connection {
                return DriverManager::getConnection(
                    $settings->get('doctrine.connection'),
                    $configuration
                );
            },
            ConnectionProvider::class => function (EntityManagerProvider $entityManagerProvider) {
                return new EntityManagerProvider\ConnectionFromManagerProvider($entityManagerProvider);
            },
        ];
    }

    protected static function addDoctrineMigrations(): array
    {
        return [
            DependencyFactory::class => function (Settings $settings, EntityManager $entityManager) {
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
            Settings::class => \DI\factory([Settings::class, 'load']),
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
            /*
            Guard::class => function (EntityManagerInterface $entityManager, Settings $settings) {
                $storage = new \App\Application\Handlers\CsrfHandler($entityManager);
                return new Guard(
                    $app->getResponseFactory(),
                    $settings->get('csrf.prefix'),
                    $storage,
                    function (ServerRequestInterface $request) {
                        throw new \App\Domain\Securekey\SecurekeyInvalidCSRFException($request);
                    }
                );
            },
            */
            RepositoryInterface::class => function (RepositoryContainer $repositoryContainer, RequestedEntry $entry) {
                return $repositoryContainer->getEntity($entry->getName());
            },
            RestInterface::class => function (RoutesContainer $routesContainer, RequestedEntry $entry) {
                return $routesContainer->getRoute($entry->getName());
            },
            ServerRequestFactoryInterface::class => \DI\get(ServerRequestFactory::class),
        ];
    }
}
