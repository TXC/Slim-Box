<?php

declare(strict_types=1);

namespace TXC\Box\Repository;

use TXC\Box\Interface\DomainInterface;

class RepositoryContainer
{
    /** @var DomainInterface[] */
    private array $entityRegister = [];

    public function registerEntity(DomainInterface $class): void
    {
        $reflection = new \ReflectionClass($class);

        if (array_key_exists($reflection->getName(), $this->getEntities())) {
            throw new \RuntimeException(sprintf('Class "%s" already registered in container', $reflection->getName()));
        }
        $this->entityRegister[$reflection->getName()] = $class;
    }

    /**
     * @return DomainInterface[]
     */
    public function getEntities(): array
    {
        return $this->entityRegister;
    }

    public function getEntity(string $className): ?DomainInterface
    {
        return $this->entityRegister[$className] ?? null;
    }
}
