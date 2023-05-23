<?php

declare(strict_types=1);

namespace TXC\Box\Interfaces;

use TXC\Box\Infrastructure\DependencyInjection\ContainerBuilder;
use TXC\Box\Infrastructure\Environment\Settings;

interface CompilerPass
{
    public function process(ContainerBuilder $container, Settings $settings): void;
}
