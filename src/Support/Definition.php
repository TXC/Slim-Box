<?php

declare(strict_types=1);

namespace TXC\Box\Support;

use Composer\InstalledVersions;
use DI\Factory\RequestedEntry;
use Doctrine\DBAL;
use Doctrine\Migrations\Configuration as MigrationConfiguration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\ORM;
use Doctrine\ORM\Tools\Console as ORMConsole;
use League\Event\EventDispatcher;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Log\LoggerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Console\Application;
use Symfony\Component\Translation;
use TXC\Box\Infrastructure\CompilerPasses\Console\ConsoleCommandContainer;
use TXC\Box\Infrastructure\CompilerPasses\Domain\DomainContainer;
use TXC\Box\Infrastructure\CompilerPasses\EventListeners\EventListenerContainer;
use TXC\Box\Infrastructure\CompilerPasses\Routes\RoutesContainer;
use TXC\Box\Infrastructure\CompilerPasses\Twig\ExtensionContainer;
use TXC\Box\Infrastructure\CompilerPasses\Twig\RuntimeLoaderContainer;
use TXC\Box\Infrastructure\CompilerPasses\Twig\TranslateResourcesContainer;
use TXC\Box\Infrastructure\Environment\Environment;
use TXC\Box\Infrastructure\Environment\Settings;
use TXC\Box\Infrastructure\Events;
use TXC\Box\Interfaces\DomainInterface;
use TXC\Box\Interfaces\RestInterface;

use function DI\get;

class Definition
{
    public static function collect(): array
    {
        $settings = Settings::load();
        $packages = [];
        if (InstalledVersions::isInstalled('monolog/monolog') && $settings->get('logger')) {
            $packages = array_merge($packages, self::addMonolog());
        }
        if (InstalledVersions::isInstalled('doctrine/dbal') && $settings->get('doctrine')) {
            $packages = array_merge($packages, self::addDoctrineDbal());
        }
        if (InstalledVersions::isInstalled('doctrine/orm') && $settings->get('doctrine')) {
            $packages = array_merge($packages, self::addDoctrineOrm());
        }
        if (InstalledVersions::isInstalled('doctrine/migrations') && $settings->get('doctrine')) {
            $packages = array_merge($packages, self::addDoctrineMigrations());
        }
        if (InstalledVersions::isInstalled('symfony/console')) {
            $packages = array_merge($packages, self::addSymfonyConsole());
        }
        if (InstalledVersions::isInstalled('slim/twig-view')) {
            $packages = array_merge($packages, self::addSlimTwigView());
        }
        if (InstalledVersions::isInstalled('slim/php-view')) {
            $packages = array_merge($packages, self::addSlimPhpView());
        }

        return array_merge($packages, self::addSkeleton());
    }

    protected static function addMonolog(): array
    {
        return [
            Logger::class => function (Settings $settings): Logger {
                $name = $settings->get('logger.prefix');

                $logger = new Logger($name);

                $logger->pushProcessor(new \Monolog\Processor\PsrLogMessageProcessor());

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

    protected static function addSlimTwigView(): array
    {
        $definitions = [
            // Twig Environment.
            \Twig\Loader\LoaderInterface::class => \DI\factory(function () {
                /** @var \Slim\Views\Twig $twig */
                $twig = \DI\get('view');
                return $twig->getLoader();
            }),
            \Twig\Environment::class => \DI\factory(function (
                Settings $settings,
                \Twig\Loader\FilesystemLoader $loader
            ) {
                /** @var \Slim\Views\Twig $twig */
                $twig = \DI\get('view');
                return $twig->getEnvironment();
            })
        ];

        if (InstalledVersions::isInstalled('symfony/translation')) {
            $definitions = array_merge($definitions, [
                \Symfony\Contracts\Translation\TranslatorInterface::class => function (
                    Settings $settings,
                    TranslateResourcesContainer $resourcesContainer
                ) {
                    $translator = new Translation\Translator(
                        $settings->get('slim.locale'),
                        new Translation\Formatter\MessageFormatter(new Translation\IdentityTranslator()),
                        $settings->get('slim.cache_dir'),
                    );
                    $translator->addLoader('mo', new Translation\Loader\MoFileLoader());
                    foreach ($resourcesContainer->getTranslations() as $locale => $path) {
                        $translator->addResource('mo', $path, $locale, 'messages');
                    }

                    return $translator;
                }
            ]);
        }
        return array_merge($definitions, [
            \Slim\Views\Twig::class => \DI\factory(function (Settings $settings) {
                return \Slim\Views\Twig::create(
                    $settings->get('slim.template_dir'),
                    $settings->get('twig')
                );
            }),
            'view' => \DI\factory(function (
                \Slim\Views\Twig $twig,
                ExtensionContainer $extensionContainer,
                RuntimeLoaderContainer $runtimeLoaderContainer
            ) {
                foreach ($extensionContainer->getExtensions() as $extension) {
                    $twig->addExtension($extension);
                }
                foreach ($runtimeLoaderContainer->getRuntimeLoaders() as $runtime) {
                    $twig->addRuntimeLoader($runtime);
                }
                return $twig;
            }),
        ]);
    }

    protected static function addSlimPhpView(): array
    {
        return [
            \Slim\Views\PhpRenderer::class => \DI\factory(function (Settings $settings) {
                return new \Slim\Views\PhpRenderer($settings->get('slim.template_dir'));
            }),
        ];
    }

    protected static function addSkeleton(): array
    {
        return [
            Environment::class => function () {
                return Environment::from(getenv('ENVIRONMENT'));
            },
            // Settings.
            Settings::class => function (): Settings {
                return Settings::load();
            },
            ContainerInterface::class => \DI\get(\DI\Container::class),
            EventDispatcher::class => function (EventListenerContainer $eventListenerContainer) {
                $dispatcher = new EventDispatcher();
                foreach ($eventListenerContainer->getListeners() as $class) {
                    $dispatcher->subscribeListenersFrom($class);
                }
                return $dispatcher;
            },
            /*
            \League\Event\Listener::class => function (RequestedEntry $entry) {
                return \DI\autowire($entry->getName());
            },
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
            //RestInterface::class => function (RoutesContainer $routesContainer, RequestedEntry $entry) {
            //    return $routesContainer->getRoute($entry->getName());
            //},
            ServerRequestFactoryInterface::class => \DI\get(ServerRequestFactory::class),
        ];
    }
}
