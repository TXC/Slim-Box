<?php

declare(strict_types=1);

namespace TXC\Box\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Redis;
use RedisException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpException;
use TXC\Box\Infrastructure\Controller\RequestResponse;
use TXC\Box\Infrastructure\Environment\Settings;

class RateLimitMiddleware implements MiddlewareInterface
{
    use RequestResponse;

    protected Redis $redis;

    protected int $maxRequests = 1;

    protected int $seconds = 10;

    /** @var callable */
    protected $limitHandler;
    /** @var callable */
    protected $headerHandler;

    private Settings $settings;
    private LoggerInterface $logger;
    private int $numberOfRequests;
    private array $storedRequests;

    public function __construct(Settings $settings, LoggerInterface $logger)
    {
        $this->settings = $settings;
        $this->logger = $logger;

        try {
            $this->redis = new Redis();
            $this->redis->connect(
                host: $settings->get('redis.host'),
                port: $settings->get('redis.port'),
                timeout: floatval($settings->get('redis.timeout')),
            );
            $this->auth();
        } catch (RedisException $e) {
            $this->logger->error($e->getMessage(), [$e]);
        }

        $this->maxRequests = $this->settings->get('limit.requests');
        $this->seconds = $this->settings->get('limit.period');

        $this->headerHandler = [$this, 'headerHandler'];
        $this->limitHandler = [$this, 'limitHandler'];
    }

    private function auth(): void
    {
        $auth = [];
        if (($username = $this->settings->get('redis.username')) !== null) {
            $auth[] = $username;
        }
        if (($password = $this->settings->get('redis.password')) !== null) {
            $auth[] = $password;
        }
        try {
            if (!empty($auth) && $this->redis->auth($auth) === false) {
                $this->logger->error('Authentication failed for Redis');
            }
        } catch (RedisException $e) {
            $this->logger->error($e->getMessage(), [$e]);
        }
    }

    protected function limitHandler(
        ServerRequestInterface $request,
        RequestHandlerInterface $requestHandler
    ): ResponseInterface {
        $response = new \Slim\Psr7\Response();

        $ttls = array_map(function ($key) {
            return $this->redis->ttl($key);
        }, $this->storedRequests);
        sort($ttls);

        $retryAfter = current($ttls);
        $limitReset = end($ttls);

        $date = new \DateTime('now');
        $date->modify('+' . $limitReset . ' seconds');

        return $response
            // Timestamp when to try again
            ->withHeader('X-RateLimit-Reset', $date->format('c'))
            // The number of seconds left on the current period
            ->withHeader('Retry-After', $retryAfter)
            ->withStatus(429);
    }

    public function setRequestsPerSecond(int $maxRequests, int $seconds): void
    {
        $this->maxRequests = $maxRequests;
        $this->seconds = $seconds;
    }

    public function setHandler(callable $handler): void
    {
        $this->limitHandler = $handler;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $this->setRequest($request);
        $this->setRequestHandler($handler);

        $response = $this->lookUpKey($this->getKeyValue());
        return call_user_func(
            $this->headerHandler,
            $response
        );
    }

    private function getKeyValue(): string
    {
        $keyName = $this->settings->get('authentication.header');
        if (!str_starts_with('x-', strtolower($keyName))) {
            $keyName = 'x-' . $keyName;
        }

        $keys = [];
        if ($this->getRequest()->hasHeader($keyName)) {
            $keys = $this->getRequest()->getHeader($keyName);
        }
        if (!empty($keys)) {
            return current($keys);
        } elseif ($this->settings->get('limit.fallback')) {
            return str_replace('.', '', $_SERVER['REMOTE_ADDR']);
        } else {
            throw new HttpBadRequestException($this->getRequest(), 'Missing API-Key');
        }
    }

    protected function getNumberOfRequests(string $key): int
    {
        try {
            if (empty($this->storedRequests)) {
                $this->storedRequests = $this->redis->keys($key . '*');
            }
            return count($this->storedRequests);
        } catch (RedisException $e) {
            $this->logger->error($e->getMessage(), [$e]);
            return 0;
        }
    }

    private function lookUpKey(string $key): ResponseInterface
    {
        $this->numberOfRequests = $this->getNumberOfRequests($key);
        if ($this->numberOfRequests < $this->maxRequests) {
            $newKey = sprintf(
                '%s%s',
                $key, //str_replace('.', '', $_SERVER['REMOTE_ADDR']),
                mt_rand()
            );
            try {
                $this->redis->set($newKey, time());
                $this->redis->expire($newKey, $this->seconds);
            } catch (RedisException $e) {
                $this->logger->error($e->getMessage(), [$e]);
                throw new HttpException($this->getRequest(), 'Unknown Error', 500);
            }

            return $this->getRequestHandler()->handle($this->getRequest());
        }
        return call_user_func(
            $this->limitHandler,
            $this->getRequest(),
            $this->getRequestHandler()
        );
    }

    private function headerHandler(ResponseInterface $response): ResponseInterface
    {
        return $response
            // The number of allowed requests in the current period
            ->withHeader('X-RateLimit-Limit', $this->maxRequests)
            // The number of remaining requests in the current period
            ->withHeader('X-RateLimit-Remaining', $this->maxRequests - $this->numberOfRequests);
    }
}
