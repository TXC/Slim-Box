<?php

declare(strict_types=1);

namespace TXC\Box\Middlewares;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Whoops\Handler\JsonResponseHandler;
use Zeuxisoo\Whoops\Slim\WhoopsGuard;

class WhoopsMiddleware implements MiddlewareInterface
{
    protected $settings = [];
    protected $handlers = [];

    /**
     * Instance the whoops middleware object
     *
     * @param array $settings
     * @param array $handlers
     */
    public function __construct(array $settings = [], array $handlers = []) {
        $this->settings = $settings;
        $this->handlers = $handlers;
    }

    /**
     * Handle the requests
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        if ($request->hasHeader('Accept') && str_contains(strtolower($request->getHeaderLine('Accept')), 'application/json')) {
            $jsonHandler = new JsonResponseHandler();
            array_unshift($this->handlers, $jsonHandler);
        }
        $whoopsGuard = new WhoopsGuard($this->settings);
        $whoopsGuard->setRequest($request);
        $whoopsGuard->setHandlers($this->handlers);
        $whoopsGuard->install();

        return $handler->handle($request);
    }

}
