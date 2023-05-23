<?php

namespace TXC\Box\Interfaces;

interface ActionPayloadInterface
{
    public function getStatusCode(): int;

    public function getData();

    public function getError(): ?ActionErrorInterface;
}
