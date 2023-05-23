<?php

declare(strict_types=1);

namespace TXC\Box\Testing;

use Slim\App;
use TXC\Box\Infrastructure\Application\Application;
use TXC\Box\Infrastructure\DependencyInjection\ContainerFactory;

trait WithApplication
{
    use WithContainer;

    private static ?App $application = null;

    protected static function bootApplication(): App
    {
        if (!self::$application) {
            $container = ContainerFactory::createForTestSuite();
            self::$application = Application::create($container);
        }

        return self::$application;
    }

    public static function getApplication(): App
    {
        return self::$application;
    }
}
