<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier\Imp\PacketeryDPD;

class CountrySelect extends \Packetery\Checkout\Model\Carrier\Config\AbstractCountrySelect
{

    protected function getCountryIds(): array
    {
        return ['CZ', 'HU', 'DE', 'RO', 'AT', 'SI'];
    }
}
