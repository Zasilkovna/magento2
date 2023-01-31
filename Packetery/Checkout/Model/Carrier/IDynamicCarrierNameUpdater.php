<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier;

interface IDynamicCarrierNameUpdater
{
    /**
     * @param string $carrierName
     * @param \Packetery\Checkout\Model\Carrier\AbstractDynamicCarrier|null $dynamicCarrier
     */
    public function updateDynamicCarrierName(string $carrierName, ?AbstractDynamicCarrier $dynamicCarrier = null): void;
}
