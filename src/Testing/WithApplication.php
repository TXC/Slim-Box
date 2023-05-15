<?php

declare(strict_types=1);

namespace TXC\Box\Testing;

use TXC\Box\Application\Application;
use TXC\Box\DependencyInjection\ContainerFactory;
use Slim\App;

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
