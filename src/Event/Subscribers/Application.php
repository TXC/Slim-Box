<?php

declare(strict_types=1);

namespace TXC\Box\Event\Subscribers;

use League\Event\ListenerRegistry;
use League\Event\ListenerSubscriber;
use Psr\Container\ContainerInterface;
use TXC\Box\Event\Listeners\ApplicationReady;
use TXC\Box\Event\Listeners\ContainerReady;

class Application implements ListenerSubscriber
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    public function subscribeListeners(ListenerRegistry $acceptor): void
    {
        $acceptor->subscribeTo('container.ready', new ContainerReady($this->container));
        $acceptor->subscribeTo('application.ready', new ApplicationReady($this->container));
    }
}
