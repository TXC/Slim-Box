<?php

declare(strict_types=1);

namespace TXC\Box\Interface;

use TXC\Box\DependencyInjection\ContainerBuilder;

interface RoutePass
{
    public function process(ContainerBuilder $container): void;
}
