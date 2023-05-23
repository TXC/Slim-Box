<?php

declare(strict_types=1);

namespace TXC\Box\Interfaces;

interface ActionPayloadInterface
{
    public function getStatusCode(): int;

    public function getData();

    public function getError(): ?ActionErrorInterface;
}
