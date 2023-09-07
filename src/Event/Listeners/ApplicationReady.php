<?php

declare(strict_types=1);

namespace TXC\Box\Event\Listeners;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Factory\ServerRequestCreatorFactory;
use TXC\Box\Handlers\HttpErrorHandler;
use TXC\Box\Handlers\ShutdownHandler;
use TXC\Box\Infrastructure\CompilerPasses\Middleware\MiddlewareContainer;
use TXC\Box\Infrastructure\CompilerPasses\Routes\RoutesContainer;
use TXC\Box\Infrastructure\Environment\Environment;
use TXC\Box\Infrastructure\Environment\Settings;
use Zeuxisoo\Whoops\Slim\WhoopsMiddleware;

class ApplicationReady implements \League\Event\Listener
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    public function __invoke(object $event): void
    {
        $app = $this->container->get(App::class);
        // 
        // !! Middlewares are LIFO (Last In, First Out) !!
        // 

        $this->resolveMiddleware($app);
        $this->resolveRouteConfig($app);
        //$this->resolveRoutePasses($app);

        // Add Body Parsing Middleware
        $app->addBodyParsingMiddleware();

        // Add Routing Middleware
        $app->addRoutingMiddleware();


        $this->addErrorHandlingMiddleware($app);
    }

    public function resolveRoutePasses(App $app): void
    {
        $routesContainer = $this->container->get(RoutesContainer::class);
        foreach ($routesContainer->getRoutes() as $route) {
            $route->addTo($app);
        }
    }

    public function resolveRouteConfig(App $app): void
    {
        // Register routes
        if (file_exists(Settings::getAppRoot() . '/config/routes.php')) {
            $routes = require $appRoot = Settings::getAppRoot() . '/config/routes.php';
            $routes($app);
        }
    }

    private function addErrorHandlingMiddleware(App $app): void
    {
        $settings = $this->container->get(Settings::class);

        if (
            Environment::DEV === Environment::from($_ENV['ENVIRONMENT'])
            && $settings->get('slim.displayErrorDetails')
        ) {
            $app->add(new \TXC\Box\Middlewares\WhoopsMiddleware());
            //$app->add(new WhoopsMiddleware());
            return;
        }

        $errorMiddleware = $app->addErrorMiddleware(
            $settings->get('slim.displayErrorDetails'),
            $settings->get('slim.logErrors'),
            $settings->get('slim.logErrorDetails'),
        );

        $errorHandler = new HttpErrorHandler($app->getCallableResolver(), $app->getResponseFactory());
        $errorMiddleware->setDefaultErrorHandler($errorHandler);

        $shutdownHandler = new ShutdownHandler(
            ServerRequestCreatorFactory::create()->createServerRequestFromGlobals(),
            $errorHandler,
            $settings->get('slim.displayErrorDetails'),
            $app->getContainer()->get(LoggerInterface::class)
        );
        register_shutdown_function($shutdownHandler);
        return;
    }

    private function resolveMiddleware(App $app): void
    {
        $settings = $this->container->get(Settings::class);
        $middlewareContainer = $this->container->get(MiddlewareContainer::class);

        $allowedMiddleware = $settings->get('passes.middleware');
        foreach ($allowedMiddleware as $middleware) {
            $app->add($middlewareContainer->getMiddleware($middleware));
        }
        $middleware = require $appRoot = Settings::getAppRoot() . '/config/middleware.php';
        $middleware($app);
    }
}
