<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Config\Source;

class CarrierLabelFormat extends LabelFormat
{
    public function __construct()
    {
        $this->keys = \Packetery\Checkout\Model\Label\LabelFormats::getCarrierFormatKeys();
    }
}

