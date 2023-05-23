<?php

declare(strict_types=1);

namespace TXC\Box\Interfaces;

use TXC\Box\Infrastructure\DependencyInjection\ContainerBuilder;

interface RoutePass
{
    public function process(ContainerBuilder $container): void;
}
