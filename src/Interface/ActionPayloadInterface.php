<?php

namespace TXC\Box\Interface;

interface ActionPayloadInterface
{
    public function getStatusCode(): int;

    public function getData();

    public function getError(): ?ActionErrorInterface;
}
