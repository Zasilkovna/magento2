<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier;

class Facade
{
    /**
     * @var \Magento\Shipping\Model\Config
     */
    protected $shippingConfig;

    /**
     * Facade constructor.
     *
     * @param \Magento\Shipping\Model\Config $shippingConfig
     */
    public function __construct(\Magento\Shipping\Model\Config $shippingConfig)
    {
        $this->shippingConfig = $shippingConfig;
    }

    /**
     * @return AbstractCarrier[]
     */
    public function getActiveCarriers()
    {
        $result = [];
        $carriers = $this->shippingConfig->getActiveCarriers();
        foreach ($carriers as $carrier) {
            if ($carrier instanceof AbstractCarrier) {
                $result[] = $carrier;
            }
        }

        return $result;
    }
}
