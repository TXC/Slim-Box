<?php

declare(strict_types=1);

namespace TXC\Box\Application;

use TXC\Box\Controller\RoutesContainer;
use TXC\Box\DependencyInjection\ContainerFactory;
use TXC\Box\Environment\Environment;
use TXC\Box\Environment\Settings;
use TXC\Box\Handler\HttpErrorHandler;
use TXC\Box\Handler\ShutdownHandler;
use TXC\Box\Middleware\MiddlewareContainer;
use DI\Bridge\Slim\Bridge;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Factory\ServerRequestCreatorFactory;
use Zeuxisoo\Whoops\Slim\WhoopsMiddleware;

final class Application
{
    private function __construct(private App $app)
    {
    }

    public static function create(?ContainerInterface $container = null): App
    {
        $container ?? ContainerFactory::create();
        $app = Bridge::create($container);

        $application = new static($app);
        $application->resolveRoutePasses()
                    ->addErrorHandlingMiddleware()
                    ->resolveMiddleware();

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
            Environment::DEV !== Environment::from($_ENV['ENVIRONMENT'])
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
            $settings->get('slim.displayErrorDetails')
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
