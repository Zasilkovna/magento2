<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Api\Request;

class PacketCourierNumberRequest
{
    private string $apiPassword;

    private string $packetId;

    public function __construct(string $apiPassword, string $packetId)
    {
        $this->apiPassword = $apiPassword;
        $this->packetId = $packetId;
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

