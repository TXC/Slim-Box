<?php

declare(strict_types=1);

namespace TXC\Box\Infrastructure\Application;

use DI\Bridge\Slim\Bridge;
use League\Event\EventDispatcher;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Factory\ServerRequestCreatorFactory;
use TXC\Box\Handlers\HttpErrorHandler;
use TXC\Box\Handlers\ShutdownHandler;
use TXC\Box\Infrastructure\CompilerPasses\Middleware\MiddlewareContainer;
use TXC\Box\Infrastructure\CompilerPasses\Routes\RoutesContainer;
use TXC\Box\Infrastructure\DependencyInjection\ContainerFactory;
use TXC\Box\Infrastructure\Environment\Environment;
use TXC\Box\Infrastructure\Environment\Settings;
use TXC\Box\Infrastructure\Events;
use Zeuxisoo\Whoops\Slim\WhoopsMiddleware;

final class Application
{
    private function __construct(private App $app)
    {
    }

    public static function create(?ContainerInterface $container = null): App
    {
        $container = $container ?? ContainerFactory::create();
        $app = Bridge::create($container);

        $dispatcher = $container->get(EventDispatcher::class);
        $dispatcher->dispatch(new Events\Event('application.ready'));

        $application = new static($app);
        $application->resolveRoutePasses()
                    ->addErrorHandlingMiddleware()
                    ->resolveMiddleware();

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

    public function resolveRoutePasses(): self
    {
        $routesContainer = $this->app->getContainer()->get(RoutesContainer::class);
        foreach ($routesContainer->getRoutes() as $route) {
            $route->addTo($this->app);
        }

        // Register routes
        if (file_exists(Settings::getAppRoot() . '/config/routes.php')) {
            (require $appRoot = Settings::getAppRoot() . '/config/routes.php')($this->app);
        }

        return $this;
    }

    private function addErrorHandlingMiddleware(): self
    {
        $settings = $this->app->getContainer()->get(Settings::class);

        if (
            Environment::DEV === Environment::from($_ENV['ENVIRONMENT'])
            && $settings->get('slim.displayErrorDetails')
        ) {
            $this->app->add(new WhoopsMiddleware());
            return $this;
        }

        $errorMiddleware = $this->app->addErrorMiddleware(
            $settings->get('slim.displayErrorDetails'),
            $settings->get('slim.logErrors'),
            $settings->get('slim.logErrorDetails'),
        );

        $errorHandler = new HttpErrorHandler($this->app->getCallableResolver(), $this->app->getResponseFactory());
        $errorMiddleware->setDefaultErrorHandler($errorHandler);

        $shutdownHandler = new ShutdownHandler(
            ServerRequestCreatorFactory::create()->createServerRequestFromGlobals(),
            $errorHandler,
            $settings->get('slim.displayErrorDetails'),
            $this->app->getContainer()->get(LoggerInterface::class)
        );
        register_shutdown_function($shutdownHandler);

        return $this;
    }

    // Register middleware
    private function resolveMiddleware(): self
    {
        // Add Body Parsing Middleware
        $this->app->addBodyParsingMiddleware();

        // Add Routing Middleware
        $this->app->addRoutingMiddleware();

        $middlewareContainer = $this->app->getContainer()->get(MiddlewareContainer::class);
        foreach ($middlewareContainer->getMiddleware() as $middleware) {
            $this->app->add($middleware);
        }
        (require $appRoot = Settings::getAppRoot() . '/config/middleware.php')($this->app);

        return $this;
    }
}
