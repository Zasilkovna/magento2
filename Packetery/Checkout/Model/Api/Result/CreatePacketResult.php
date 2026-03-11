<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Api\Result;

class CreatePacketResult
{
    private string $packetId;

    public function __construct(string $packetId)
    {
        $this->packetId = $packetId;
    }

    public function getPacketId(): string
    {
        return $this->packetId;
    }
}
