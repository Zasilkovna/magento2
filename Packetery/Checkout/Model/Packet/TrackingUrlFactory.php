<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Packet;

class TrackingUrlFactory
{
    private const BASE_URL = 'https://tracking.packeta.com/';

    private const BARCODE_PREFIX = 'Z';

    public function create(string $packetNumber): string
    {
        return self::BASE_URL . rawurlencode($this->formatNumber($packetNumber));
    }

    public function formatNumber(string $packetNumber): string
    {
        return self::BARCODE_PREFIX . $packetNumber;
    }
}
