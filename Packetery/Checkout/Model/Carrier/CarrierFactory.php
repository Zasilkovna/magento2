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
        Cache $cache,
        string $carrierCode,
        int $storeId
    ): ?\Magento\Shipping\Model\Carrier\AbstractCarrier {
        if (!$cache->has($storeId, $carrierCode)) {
            $cache->set($storeId, $carrierCode, $this->create($carrierCode, $storeId));
        }

        return $cache->get($storeId, $carrierCode);
    }

    public function create(string $carrierCode, int $storeId): ?\Magento\Shipping\Model\Carrier\AbstractCarrier
    {
        $carrier = $this->carrierFactory->create($carrierCode, $storeId);

        return ($carrier instanceof \Magento\Shipping\Model\Carrier\AbstractCarrier) ? $carrier : null;
    }
}
