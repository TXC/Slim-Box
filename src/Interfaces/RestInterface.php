<?php

declare(strict_types=1);

namespace TXC\Box\Interfaces;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface RestInterface
{
    public function index(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface;

    public function store(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface;

    public function show(
        ServerRequestInterface $request,
        ResponseInterface $response,
        int $entityId
    ): ResponseInterface;

    public function update(
        ServerRequestInterface $request,
        ResponseInterface $response,
        int $entityId
    ): ResponseInterface;

    public function destroy(
        ServerRequestInterface $request,
        ResponseInterface $response,
        int $entityId
    ): ResponseInterface;
}
