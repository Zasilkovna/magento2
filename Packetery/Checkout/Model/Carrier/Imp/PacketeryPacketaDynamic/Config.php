<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier\Imp\PacketeryPacketaDynamic;

use Packetery\Checkout\Model\Carrier\Methods;
use Packetery\Checkout\Model\Label\LabelFormats;

class Config extends \Packetery\Checkout\Model\Carrier\Config\AbstractConfig
{
    /**
     * @return string[]
     */
    public function getAllowedMethods(): array
    {
        return [Methods::PICKUP_POINT_DELIVERY, Methods::DIRECT_ADDRESS_DELIVERY];
    }

    protected function normalizeLabelFormatValue(string $value): string
    {
        return LabelFormats::normalizeCarrierFormat($value);
    }
}
