<?php

declare(strict_types=1);

namespace TXC\Box\Repository;

use TXC\Box\Exception\EntityNotFound;
use TXC\Box\Interface\RepositoryInterface;

class MemoryRepository implements RepositoryInterface
{
    private array $elements;

    public function __construct(array $elements = [])
    {
        $this->elements = $elements;
    }

    public function find($id)
    {
        if (!isset($this->elements[$id])) {
            throw new EntityNotFound();
        }

        return $this->elements[$id];
    }

    public function findAll(): array
    {
        return array_values($this->elements);
    }
}
