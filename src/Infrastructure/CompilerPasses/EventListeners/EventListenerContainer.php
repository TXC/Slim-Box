<?php

declare(strict_types=1);

namespace TXC\Box\Infrastructure\CompilerPasses\EventListeners;

use League\Event\ListenerSubscriber;

class EventListenerContainer
{
    /** @var ListenerSubscriber[] */
    private array $listeners = [];

    public function registerListener(ListenerSubscriber $class): void
    {
        $reflection = new \ReflectionClass($class);

        if (array_key_exists($reflection->getName(), $this->getListeners())) {
            throw new \RuntimeException(sprintf('Class "%s" already registered in container', $reflection->getName()));
        }
        $this->listeners[$reflection->getName()] = $class;
    }

    /**
     * @return ListenerSubscriber[]
     */
    public function getListeners(): array
    {
        return $this->listeners;
    }

    public function getListener(string $pattern): ?ListenerSubscriber
    {
        return $this->listeners[$pattern] ?? null;
    }
}
