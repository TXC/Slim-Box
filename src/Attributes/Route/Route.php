<?php

declare(strict_types=1);

namespace TXC\Box\Attributes\Route;

use Slim\Interfaces\RouteCollectorProxyInterface;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Route extends GroupRoute
{
    public const GET = 'get';
    public const POST = 'post';
    public const PUT = 'put';
    public const PATCH = 'patch';
    public const DELETE = 'delete';
    public const OPTIONS = 'options';
    public const ANY = 'any';

    public function __construct(
        protected string $pattern,
        protected string $verb,
        protected ?string $name = null,
        protected ?array $middleware = null
    ) {
        parent::__construct(pattern: $this->pattern, middleware: $this->middleware);
    }

    public function isRoute(): bool
    {
        return true;
    }

    public function hasName(): bool
    {
        return $this->name !== null;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getVerb(): array
    {
        switch ($this->verb) {
            case self::GET:
            case self::POST:
            case self::PUT:
            case self::PATCH:
            case self::DELETE:
            case self::OPTIONS:
                return [$this->verb];
            case self::ANY:
                return [
                    self::GET,
                    self::POST,
                    self::PUT,
                    self::PATCH,
                    self::DELETE,
                    self::OPTIONS
                ];
            default:
                throw new \RuntimeException('Invalid HTTP Verb "' . $this->verb . '"');
        }
    }

    public function setVerb(string $verb): self
    {
        $this->verb = $verb;

        return $this;
    }

    public function addTo(RouteCollectorProxyInterface $app): void
    {
        $route = $app->map($this->getVerb(), $this->getPattern(), $this->getCallable());
        if ($this->hasName()) {
            $route->setName($this->getName());
        }
        if ($this->hasMiddleware()) {
            $route->add($this->getMiddleware());
        }
    }
}
