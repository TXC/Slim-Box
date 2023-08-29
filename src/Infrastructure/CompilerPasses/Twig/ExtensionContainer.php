<?php

declare(strict_types=1);

namespace TXC\Box\Infrastructure\CompilerPasses\Twig;

use Twig\Extension\ExtensionInterface;

class ExtensionContainer
{
    /** @var ExtensionInterface[] */
    private array $extensions = [];

    public function registerExtension(ExtensionInterface $class): void
    {
        $reflection = new \ReflectionClass($class);

        if (array_key_exists($reflection->getName(), $this->getExtensions())) {
            throw new \RuntimeException(sprintf('Class "%s" already registered in container', $reflection->getName()));
        }
        $this->extensions[$reflection->getName()] = $class;
    }

    /**
     * @return ExtensionInterface[]
     */
    public function getExtensions(): array
    {
        return $this->extensions;
    }

    public function getExtension(string $pattern): ?ExtensionInterface
    {
        return $this->extensions[$pattern] ?? null;
    }
}
