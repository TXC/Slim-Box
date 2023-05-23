<?php

declare(strict_types=1);

namespace TXC\Box\Attribute\Route;

use Psr\Http\Server\MiddlewareInterface;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Middleware
{
    /**
     * @param MiddlewareInterface|string|callable $middleware
     */
    public function __construct(
        private $middleware
    ) {
    }

    public function getMiddleware(): callable
    {
        return $this->middleware;
    }
}
