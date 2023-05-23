<?php

declare(strict_types=1);

namespace TXC\Box\Infrastructure\CompilerPasses\Repository;

use TXC\Box\Interfaces\RepositoryInterface;

class RepositoryContainer
{
    /** @var RepositoryInterface[] */
    private array $repositoryRegister = [];

    public function registerRepository(RepositoryInterface $class): void
    {
        $reflection = new \ReflectionClass($class);

        if (array_key_exists($reflection->getName(), $this->getRepositories())) {
            throw new \RuntimeException(sprintf('Class "%s" already registered in container', $reflection->getName()));
        }
        $this->repositoryRegister[$reflection->getName()] = $class;
    }

    /**
     * @return RepositoryInterface[]
     */
    public function getRepositories(): array
    {
        return $this->repositoryRegister;
    }

    public function getRepository(string $className): ?RepositoryInterface
    {
        return $this->repositoryRegister[$className] ?? null;
    }
}
