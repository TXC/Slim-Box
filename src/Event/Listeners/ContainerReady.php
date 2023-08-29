<?php

declare(strict_types=1);

namespace TXC\Box\Event\Listeners;

use Psr\Container\ContainerInterface;

class ContainerReady implements \League\Event\Listener
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    public function __invoke(object $event): void
    {
        // TODO: Implement __invoke() method.
    }
}
