<?php

declare(strict_types=1);

namespace TXC\Box\Actions;

use TXC\Box\Interfaces\ActionErrorInterface;
use TXC\Box\Interfaces\ActionPayloadInterface;
use JsonSerializable;

class ActionPayload implements ActionPayloadInterface, JsonSerializable
{
    private int $statusCode;

    /**
     * @var array|object|null
     */
    private $data;

    private ?ActionErrorInterface $error;

    public function __construct(
        int $statusCode = 200,
        $data = null,
        ?ActionErrorInterface $error = null
    ) {
        $this->statusCode = $statusCode;
        $this->data = $data;
        $this->error = $error;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array|null|object
     */
    public function getData()
    {
        return $this->data;
    }

    public function getError(): ?ActionErrorInterface
    {
        return $this->error;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        $payload = [
            'statusCode' => $this->statusCode,
        ];

        if ($this->data !== null) {
            $payload['data'] = $this->data;
        } elseif ($this->error !== null) {
            $payload['error'] = $this->error;
        }

        return $payload;
    }
}
