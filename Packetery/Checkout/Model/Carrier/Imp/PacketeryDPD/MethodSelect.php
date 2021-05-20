<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier\Imp\PacketeryDPD;

use Packetery\Checkout\Model\Carrier\Methods;

class MethodSelect extends \Packetery\Checkout\Model\Carrier\Config\AbstractMethodSelect
{

    protected function createOptions(): array
    {
        return [
            ['value' => Methods::ADDRESS_DELIVERY, 'label' => __('Address Delivery Method')],
        ];
    }
}
