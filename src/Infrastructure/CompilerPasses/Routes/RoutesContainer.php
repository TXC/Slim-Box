<?php

declare(strict_types=1);

namespace TXC\Box\Infrastructure\CompilerPasses\Routes;

use TXC\Box\Attribute\Route\GroupRoute;
use TXC\Box\Attribute\Route\Route;

class RoutesContainer
{
    private array $routeRegister = [];

    public function registerRoute(Route $route): void
    {
        if (array_key_exists($route->getPattern(), $this->getRoutes())) {
            throw new \RuntimeException(sprintf('Route pattern "%s" already exists', $route->getPattern()));
        }

        $this->routeRegister[$route->getPattern()] = $route;
    }

    public function registerGroupRoute(GroupRoute $groupRoute): void
    {
        if (array_key_exists($groupRoute->getPattern(), $this->getRoutes())) {
            throw new \RuntimeException(sprintf('Route pattern "%s" already exists', $groupRoute->getPattern()));
        }

        foreach ($groupRoute->getRoutes() as $route) {
            if (array_key_exists($groupRoute->getPattern() . $route->getPattern(), $this->getRoutes())) {
                throw new \RuntimeException(sprintf('Route pattern "%s" already exists', $route['pattern']));
            }
        }

        $this->routeRegister[$groupRoute->getPattern()] = $groupRoute;
    }

    public function getRoutes(): array
    {
        return $this->routeRegister;
    }

    public function getRoute(string $pattern): ?GroupRoute
    {
        return $this->routeRegister[$pattern] ?? null;
    }
}
