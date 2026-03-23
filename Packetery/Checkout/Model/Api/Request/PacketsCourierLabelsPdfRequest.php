<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Api\Request;

class PacketsCourierLabelsPdfRequest
{
    private string $apiPassword;

    /** @var array<int, array{packetId: string, courierNumber: string}> */
    private array $packetIdsWithCourierNumbers;

    private int $offset;

    private string $format;

    /**
     * @param array<int, array{packetId: string, courierNumber: string}> $packetIdsWithCourierNumbers
     */
    public function __construct(
        string $apiPassword,
        array $packetIdsWithCourierNumbers,
        int $offset,
        string $format
    ) {
        $this->apiPassword = $apiPassword;
        $this->packetIdsWithCourierNumbers = $packetIdsWithCourierNumbers;
        $this->offset = $offset;
        $this->format = $format;
    }

    public function getApiPassword(): string
    {
        return $this->apiPassword;
    }

    /**
     * @return array<int, array{packetId: string, courierNumber: string}>
     */
    public function getPacketIdsWithCourierNumbers(): array
    {
        return $this->packetIdsWithCourierNumbers;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getFormat(): string
    {
        return $this->format;
    }
}

