<?php

declare(strict_types=1);

namespace TXC\Box\Attribute\Route;

use Slim\Interfaces\RouteCollectorProxyInterface;
//use DI\Definition\Definition;
//interface Definition extends RequestedEntry, \Stringable

#[\Attribute(\Attribute::TARGET_CLASS)]
class GroupRoute extends IsRoutable
{
    protected array $routes;

    /** @var callable */
    private $callable;

    /**
     * @param Middleware[] $middleware
     */
    public function __construct(
        protected string $pattern,
        protected ?array $middleware = null
    ) {
    }

    final public function getPattern(): string
    {
        return $this->pattern;
    }

    final public function setPattern(string $pattern): self
    {
        $this->pattern = $pattern;

        return $this;
    }

    final public function hasMiddleware(): bool
    {
        return $this->middleware !== null;
    }

    final public function getMiddleware(): ?array
    {
        return $this->middleware;
    }

    final public function setMiddleware($middleware): self
    {
        $this->middleware[] = $middleware;

        return $this;
    }

    final public function addRoute(Route $route): self
    {
        $this->routes[] = $route;

        return $this;
    }

    final public function addRoutes(Route ...$route): self
    {
        $this->routes = $route;

        return $this;
    }

    final public function hasRoutes(): bool
    {
        return count($this->routes) > 0;
    }

    public function isRoute(): bool
    {
        return false;
    }

    /**
     * @return Route[]
     */
    final public function getRoutes(): array
    {
        return $this->routes;
    }

    final public function setCallable(mixed $callable): self
    {
        if (!is_callable($callable, true, $callName)) {
            throw new \RuntimeException('"' . $callName . '" is not a valid callable');
        }
        $this->callable = $callable;

        return $this;
    }

    /**
     * @return ?callable
     */
    final public function getCallable()
    {
        return $this->callable;
    }

    public function addTo(RouteCollectorProxyInterface $app): void
    {
        $group = $app->group($this->getPattern(), [$this, 'addToGroup']);
        if ($this->hasMiddleware()) {
            $group->add($this->getMiddleware());
        }
    }

    final public function addToGroup(RouteCollectorProxyInterface $app): void
    {
        foreach ($this->getRoutes() as $route) {
            $route->addTo($app);
        }
    }
}
