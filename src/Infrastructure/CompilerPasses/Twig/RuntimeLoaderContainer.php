<?php

declare(strict_types=1);

namespace TXC\Box\Infrastructure\CompilerPasses\Twig;

use Twig\RuntimeLoader\RuntimeLoaderInterface;

class RuntimeLoaderContainer
{
    /** @var RuntimeLoaderInterface[] */
    private array $runtimeLoaders = [];

    public function registerRuntimeLoader(RuntimeLoaderInterface $class): void
    {
        $reflection = new \ReflectionClass($class);

        if (array_key_exists($reflection->getName(), $this->getRuntimeLoaders())) {
            throw new \RuntimeException(sprintf('Class "%s" already registered in container', $reflection->getName()));
        }
        $this->runtimeLoaders[$reflection->getName()] = $class;
    }

    /**
     * @return RuntimeLoaderInterface[]
     */
    public function getRuntimeLoaders(): array
    {
        return $this->runtimeLoaders;
    }

    public function getRuntimeLoader(string $pattern): ?RuntimeLoaderInterface
    {
        return $this->runtimeLoaders[$pattern] ?? null;
    }
}
