<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Api\Request;

class CreateShipmentRequest
{
    private string $apiPassword;

    /** @var string[] */
    private array $packetIds;

    /**
     * @param string[] $packetIds
     */
    public function __construct(string $apiPassword, array $packetIds)
    {
        $this->apiPassword = $apiPassword;
        $this->packetIds = $packetIds;
    }

    public function getApiPassword(): string
    {
        return $this->apiPassword;
    }

    /**
     * @return string[]
     */
    public function getPacketIds(): array
    {
        return $this->packetIds;
    }
}
