<?php

declare(strict_types=1);

namespace TXC\Box\Testing;

use Psr\Container\ContainerInterface;
use TXC\Box\Infrastructure\DependencyInjection\ContainerFactory;

trait WithContainer
{
    private static ?ContainerInterface $container = null;

    protected function bootContainer(): ContainerInterface
    {
        if (!self::$container) {
            self::$container = ContainerFactory::createForTestSuite();
        }

        return self::$container;
    }

    protected function getContainer(): ContainerInterface
    {
        if (!self::$container) {
            $this->bootContainer();
        }

        return self::$container;
    }
}
