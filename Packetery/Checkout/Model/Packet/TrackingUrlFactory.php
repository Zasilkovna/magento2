<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Packet;

class TrackingUrlFactory
{
    private const BASE_URL = 'https://tracking.packeta.com/';

    public function create(string $packetNumber): string
    {
        $trackingNumber = 'Z' . $packetNumber;

        return self::BASE_URL . rawurlencode($trackingNumber);
    }
}
