<?php

declare(strict_types=1);

namespace TXC\Box\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

trait RequestResponse
{
    protected ResponseInterface $response;

    protected ServerRequestInterface $request;

    protected RequestHandlerInterface $requestHandler;

    protected function setRequestResponse(ServerRequestInterface $request, ResponseInterface $response): self
    {
        $this->request = $request;
        $this->response = $response;

        return $this;
    }

    protected function setRequest(ServerRequestInterface $request): self
    {
        $this->request = $request;

        return $this;
    }

    protected function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    protected function setRequestHandler(RequestHandlerInterface $requestHandler): self
    {
        $this->requestHandler = $requestHandler;

        return $this;
    }

    protected function getRequestHandler(): RequestHandlerInterface
    {
        return $this->requestHandler;
    }

    protected function setResponse(ResponseInterface $response): self
    {
        $this->response = $response;

        return $this;
    }

    protected function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
