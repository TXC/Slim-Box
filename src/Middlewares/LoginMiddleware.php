<?php

declare(strict_types=1);

namespace TXC\Box\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Routing\RouteContext;
use TXC\Box\Infrastructure\Environment\Settings;

class LoginMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Settings $settings,
        private readonly LoggerInterface $logger
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $routeContext = RouteContext::fromRequest($request);
        } catch (\RuntimeException $e) {
            $this->logger->error($e->getMessage(), [$e]);
            return $handler->handle($request);
        }
        $route = $routeContext->getRoute();

        if (empty($route)) {
            throw new HttpNotFoundException($request);
        }

        if (
            empty($_SESSION['user'])
            && (!in_array($route->getName(), $this->settings->get('slim.route.public')))
        ) {
            $routeParser = $routeContext->getRouteParser();
            $url = $routeParser->urlFor($this->settings->get('slim.route.redirect_to'));

            $response = new \Slim\Psr7\Response();
            return $response->withHeader('Location', $url)->withStatus(302);
        }
        return $handler->handle($request);
    }

    /*
    public function doLogin(ServerRequestInterface $request, ResponseInterface $response)
    {
        $body =  $request->getParsedBody();
        $user = $body['user'];
        $pass = $body['pass'];

        $data = $this->user->getByLogin($user);
        if ($data['pass'] == $pass) {
            $_SESSION['user'] = $data['id'];
            //$um = new \App\Domain\User\User();
            $this->user->setLastLogin();
            return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        }
        unset($_SESSION['user']);

        return $response->withHeader('Content-Type', 'application/json')
            ->withStatus(403);
    }
    */
}
