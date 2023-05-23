<?php

declare(strict_types=1);

namespace TXC\Box\Event\Subscribers;

use League\Event\ListenerRegistry;
use League\Event\ListenerSubscriber;
use TXC\Box\Event\Listeners\ApplicationReady;
use TXC\Box\Event\Listeners\ContainerReady;

class Application implements ListenerSubscriber
{
    public function subscribeListeners(ListenerRegistry $acceptor): void
    {
        $acceptor->subscribeTo('container.ready', new ContainerReady());
        $acceptor->subscribeTo('application.ready', new ApplicationReady());
    }
}
