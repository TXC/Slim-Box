<?php

declare(strict_types=1);

namespace TXC\Box\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TXC\Box\Infrastructure\Environment\Settings;
use Psr\Log\LoggerInterface;

class CORSMiddleware implements MiddlewareInterface
{
    public function __construct(
        private Settings $settings,
        private LoggerInterface $logger
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $origin = $this->settings->get('cors.origin');
        if (is_array($origin)) {
            $origin = implode(',', $origin);
        }

        if ($origin != '*') {
            foreach ($request->getHeader('origin') as $requestOrigin) {
                if (str_contains($origin, $requestOrigin)) {
                    return $response->withStatus(403);
                }
            }
        }

        $result = $response->withHeader('Access-Control-Allow-Origin', $origin)
                        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
        //var_dump($result);
        return $result;
    }
}
