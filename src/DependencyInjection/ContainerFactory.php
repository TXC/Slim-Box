<?php

declare(strict_types=1);

namespace TXC\Box\DependencyInjection;

use TXC\Box\Application\Application;
use TXC\Box\Environment\Environment;
use TXC\Box\Environment\Settings;
use TXC\Box\Support\CompilerPass;
use TXC\Box\Support\Definition;
use TXC\Box\Support\RoutePass;
use DI\Bridge\Slim\Bridge;
use Dotenv\Dotenv;
use Psr\Container\ContainerInterface;
use Slim\App;

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

        // Autowiring are enabled by default
        //$containerBuilder->useAutoWiring($settings->get('slim.autowiring'));
        // Attributes are disabled by default
        //$containerBuilder->useAttributes($settings->get('slim.attributes'));

        if (Environment::PRODUCTION === Environment::from($_ENV['ENVIRONMENT'])) {
            // Compile and cache container.
            $containerBuilder->enableCompilation(
                $settings->get('slim.cache_dir') . '/container'
            );
            $containerBuilder->enableClassAttributeCache(
                $settings->get('slim.cache_dir') . '/class-attributes'
            );
            $containerBuilder->enableInterfaceCache(
                $settings->get('slim.cache_dir') . '/interfaces'
            );
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

        return $containerBuilder->build();
    }

    public static function createForTestSuite(): ContainerInterface
    {
        return static::create('.env.test');
    }
}
