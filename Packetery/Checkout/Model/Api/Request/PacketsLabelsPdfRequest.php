<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Api\Request;

class PacketsLabelsPdfRequest
{
    private string $apiPassword;

    /** @var string[] */
    private array $packetIds;

    private string $format;

    private int $offset;

    /**
     * @param string[] $packetIds
     */
    public function __construct(string $apiPassword, array $packetIds, string $format, int $offset)
    {
        $this->apiPassword = $apiPassword;
        $this->packetIds = $packetIds;
        $this->format = $format;
        $this->offset = $offset;
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

    public function getFormat(): string
    {
        return $this->format;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }
}

