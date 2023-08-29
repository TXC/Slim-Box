<?php

declare(strict_types=1);

namespace TXC\Box\Middlewares;

use Negotiation\Exception\Exception;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use TXC\Box\Infrastructure\Controller\RequestResponse;
use TXC\Box\Infrastructure\Environment\Settings;
use Slim\Views\Twig;

class LanguageMiddleware implements MiddlewareInterface
{
    use RequestResponse;

    private readonly Settings $settings;
    private readonly Twig $twig;

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly LoggerInterface $logger
    ) {
        $this->settings = $this->container->get(Settings::class);
        $this->twig = $this->container->get('view');
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->setRequest($request);
        $this->setRequestHandler($handler);

        $translator = $this->container->get(\Symfony\Contracts\Translation\TranslatorInterface::class);
        // $translator->addResource('mo', "locale/$lang/LC_MESSAGES/messages.mo", $lang);

        $negotiator = new \Negotiation\LanguageNegotiator();
        $acceptLanguageHeader = $request->getHeaderLine('Accept-Language');
        $availableLocales = $this->settings->get('slim.available_locales');
        $defaultLocale = $this->settings->get('slim.locale');
        if (!in_array($defaultLocale, $availableLocales)) {
            array_unshift($availableLocales, $defaultLocale);
        }
        $priorities = array_map(function ($value) {
            return implode('-', explode('_', $value));
        }, $availableLocales);
        try {
            $bestLanguage = $negotiator->getBest($acceptLanguageHeader, $priorities);
            $value = $bestLanguage->getValue();
            $type = $bestLanguage->getType();
            $quality = $bestLanguage->getQuality();
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), [$e]);
            $value = current($priorities);
        }

        if (!$this->getRequest()->getAttribute('session', false)) {
            session_start();
            $this->getRequest()->withAttribute('session', $_SESSION);
            $_SESSION['locale'] = $value;
        }
        return $this->getRequestHandler()->handle($this->getRequest());
    }
}
