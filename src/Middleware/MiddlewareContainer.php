<?php

declare(strict_types=1);

namespace TXC\Box\Middleware;

use Psr\Http\Server\MiddlewareInterface;

class MiddlewareContainer
{
    /** @var MiddlewareInterface[] */
    private array $middlewares = [];

    public function registerMiddleware(MiddlewareInterface $class): void
    {
        $reflection = new \ReflectionClass($class);

        if (array_key_exists($reflection->getName(), $this->getMiddleware())) {
            throw new \RuntimeException(sprintf('Class "%s" already registered in container', $reflection->getName()));
        }
        $this->middlewares[$reflection->getName()] = $class;
    }

    /**
     * @return MiddlewareInterface[]
     */
    public function getMiddleware(): array
    {
        return $this->middlewares;
    }
}
