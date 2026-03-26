<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Api\Request;

class CancelPacketRequest
{
    public function __construct(
        private readonly string $apiPassword,
        private readonly string $packetId
    ) {
    }

    public function getApiPassword(): string
    {
        return $this->apiPassword;
    }

    public function getPacketId(): string
    {
        return $this->packetId;
    }
}
