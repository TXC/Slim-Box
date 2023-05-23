<?php

declare(strict_types=1);

namespace TXC\Box\Infrastructure\Events;

class Event implements \League\Event\HasEventName
{
    public function __construct(private readonly string $name)
    {
    }

    public function eventName(): string
    {
        return $this->name;
    }
}
