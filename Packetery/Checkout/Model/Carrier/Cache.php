<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier;

class Cache
{
    /** @var array<int, array<string, \Magento\Shipping\Model\Carrier\AbstractCarrier|null>> */
    private array $data = [];

    public function has(int $storeId, string $carrierCode): bool
    {
        return isset($this->data[$storeId]) && array_key_exists($carrierCode, $this->data[$storeId]);
    }

    public function get(int $storeId, string $carrierCode): ?\Magento\Shipping\Model\Carrier\AbstractCarrier
    {
        return $this->data[$storeId][$carrierCode] ?? null;
    }

    public function set(int $storeId, string $carrierCode, ?\Magento\Shipping\Model\Carrier\AbstractCarrier $value): void
    {
        $this->data[$storeId][$carrierCode] = $value;
    }
}
