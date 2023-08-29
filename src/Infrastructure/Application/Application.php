<?php

declare(strict_types=1);

namespace TXC\Box\Infrastructure\Application;

use DI\Bridge\Slim\Bridge;
use League\Event\EventDispatcher;
use Psr\Container\ContainerInterface;
use Slim\App;
use TXC\Box\Infrastructure\DependencyInjection\ContainerFactory;
use TXC\Box\Infrastructure\Environment\Environment;
use TXC\Box\Infrastructure\Environment\Settings;
use TXC\Box\Infrastructure\Events;

final class Application
{
    public static function create(?ContainerInterface $container = null): App
    {
        $container = $container ?? ContainerFactory::create();
        $app = Bridge::create($container);

        $container->get(EventDispatcher::class)->dispatch(new Events\Event('application.ready'));

        if (Environment::PRODUCTION === Environment::from($_ENV['ENVIRONMENT'])) {
            /**
             * To generate the route cache data, you need to set the file to one that does not exist in a writable
             * directory.
             * After the file is generated on first run, only read permissions for the file are required.
             *
             * You may need to generate this file in a development environment and committing it to your project
             * before deploying if you don't have write permissions for the directory where the cache file resides
             * on the server it is being deployed to
             */
            $routeCollector = $app->getRouteCollector();
            $routeCollector->setCacheFile($container->get(Settings::class)->get('route.cache_file'));
        }

        return $app;
    }
}
