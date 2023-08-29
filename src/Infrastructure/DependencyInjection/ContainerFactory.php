<?php

declare(strict_types=1);

namespace TXC\Box\Infrastructure\DependencyInjection;

use DI\Definition\Helper\CreateDefinitionHelper;
use Dotenv\Dotenv;
use League\Event\EventDispatcher;
use League\Event\ListenerSubscriber;
use Psr\Container\ContainerInterface;
use TXC\Box\Infrastructure\Events;
use TXC\Box\Infrastructure\Environment\Environment;
use TXC\Box\Infrastructure\Environment\Settings;
use TXC\Box\Infrastructure\Resolvers\ClassAttributeResolver;
use TXC\Box\Infrastructure\Resolvers\InterfaceResolver;
use TXC\Box\Support\CompilerPass;
use TXC\Box\Support\Definition;

class ContainerFactory
{
    public static function create(string $dotEnv = '.env'): ContainerInterface
    {
        $appRoot = Settings::getAppRoot();

        $dotenv = Dotenv::createImmutable($appRoot, $dotEnv);
        $dotenv->load();

        // At this point the container has not been built. We need to load the settings manually.
        $settings = Settings::load();
        $containerBuilder = ContainerBuilder::create();

        if (Environment::PRODUCTION === Environment::from($_ENV['ENVIRONMENT'])) {
            // Compile and cache container.
            $containerBuilder->enableCompilation($settings->get('slim.cache_dir') . '/container');
            ClassAttributeResolver::setCacheDir($settings->get('slim.cache_dir') . '/class-attributes');
            InterfaceResolver::setCacheDir($settings->get('slim.cache_dir') . '/interfaces');
        }

        $definition = Definition::collect();
        if (file_exists($appRoot . '/config/container.php')) {
            $definition = array_merge($definition, require $appRoot . '/config/container.php');
        }
        $containerBuilder->addDefinitions($definition);

        $compilerPasses = CompilerPass::collect();
        if (file_exists($appRoot . '/config/compiler-passes.php')) {
            $compilerPasses = array_merge($compilerPasses, require $appRoot . '/config/compiler-passes.php');
        }
        $containerBuilder->addCompilerPasses(...$compilerPasses);

        $container = $containerBuilder->build();

        $container->get(EventDispatcher::class)
            ->dispatch(new Events\Event('container.ready'));

        return $container;
    }

    public static function createForTestSuite(): ContainerInterface
    {
        return static::create('.env.test');
    }
}
