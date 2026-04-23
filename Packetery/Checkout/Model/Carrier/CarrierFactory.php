<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier;

class CarrierFactory
{
    /** @var \Magento\Shipping\Model\CarrierFactory */
    private $carrierFactory;

    public function __construct(
        \Magento\Shipping\Model\CarrierFactory $carrierFactory
    ) {
        $this->carrierFactory = $carrierFactory;
    }

    public function createCached(
        array &$cache,
        string $carrierCode,
        int $storeId
    ): ?\Magento\Shipping\Model\Carrier\AbstractCarrier
    {
        $cache['carriers'] = $cache['carriers'] ?? [];
        $cache['carriers'][$storeId] = $cache['carriers'][$storeId] ?? [];

        if (!array_key_exists($carrierCode, $cache['carriers'][$storeId])) {
            $carrier = $this->carrierFactory->create($carrierCode, $storeId);
            $cache['carriers'][$storeId][$carrierCode] = ($carrier instanceof \Magento\Shipping\Model\Carrier\AbstractCarrier)
                ? $carrier
                : null;
        }

        return $cache['carriers'][$storeId][$carrierCode];
    }

    public function create(string $carrierCode, int $storeId): ?\Magento\Shipping\Model\Carrier\AbstractCarrier
    {
        $carrier = $this->carrierFactory->create($carrierCode, $storeId);

        return ($carrier instanceof \Magento\Shipping\Model\Carrier\AbstractCarrier) ? $carrier : null;
    }
}

