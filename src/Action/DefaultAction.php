<?php

declare(strict_types=1);

namespace TXC\Box\Action;

use TXC\Box\Controller\RequestResponse;
use TXC\Box\Environment\Settings;
use TXC\Box\Interface\ActionPayloadInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class DefaultAction
{
    use RequestResponse;

    private ContainerInterface $container;

    protected LoggerInterface $logger;
    protected EntityManagerInterface $entityManager;
    protected Settings $settings;

    protected array $args;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->logger = $container->get(LoggerInterface::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->settings = $container->get(Settings::class);
    }

    protected function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    protected function getRepository(string $repositoryName): EntityRepository
    {
        return $this->getEntityManager()->getRepository($repositoryName);
    }

    /**
     * @return array|object
     */
    protected function getFormData()
    {
        return $this->request->getParsedBody();
    }

    /**
     * @param array|object|null $data
     */
    protected function respondWithData($data = null, int $statusCode = 200): ResponseInterface
    {
        $payload = new ActionPayload($statusCode, $data);

        return $this->respond($payload);
    }

    protected function respond(ActionPayloadInterface $payload): ResponseInterface
    {
        $json = json_encode($payload, JSON_PRETTY_PRINT);
        $this->response->getBody()->write($json);

        return $this->response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus($payload->getStatusCode());
    }
}
