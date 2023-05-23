<?php

declare(strict_types=1);

namespace TXC\Box\Actions;

use TXC\Box\Interfaces\ActionErrorInterface;
use JsonSerializable;

class ActionError implements ActionErrorInterface, JsonSerializable
{
    private string $type;

    private string $description;

    public function __construct(string $type, ?string $description = null)
    {
        $this->type = $type;
        $this->description = $description;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(?string $description = null): self
    {
        $this->description = $description;
        return $this;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type,
            'description' => _($this->description),
        ];
    }
}
