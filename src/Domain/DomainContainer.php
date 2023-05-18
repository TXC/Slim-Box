<?php

declare(strict_types=1);

namespace TXC\Box\Domain;

use TXC\Box\Interface\DomainInterface;

class DomainContainer
{
    /** @var DomainInterface[] */
    private array $domainRegister = [];

    public function registerDomain(DomainInterface $class): void
    {
        $reflection = new \ReflectionClass($class);

        if (array_key_exists($reflection->getName(), $this->getDomains())) {
            throw new \RuntimeException(sprintf('Class "%s" already registered in container', $reflection->getName()));
        }
        $this->domainRegister[$reflection->getName()] = $class;
    }

    /**
     * @return DomainInterface[]
     */
    public function getDomains(): array
    {
        return $this->domainRegister;
    }

    public function getDomain(string $className): ?DomainInterface
    {
        return $this->domainRegister[$className] ?? null;
    }
}
