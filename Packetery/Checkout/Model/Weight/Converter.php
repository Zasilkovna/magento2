<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Weight;

class Converter
{
    public function __construct(
        readonly private \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
    }

    public function convertToKg(?float $weight, ?int $storeId = null): ?float
    {
        if ($weight === null || $weight < 0) {
            return null;
        }

        $unit = $this->getStoreWeightUnit($storeId);
        if ($unit === null) {
            return null;
        }

        return $weight * $unit->getMultiplier();
    }

    public function getStoreWeightUnit(?int $storeId = null): ?Unit
    {
        $rawUnit = $this->scopeConfig->getValue('general/locale/weight_unit', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);

        return Unit::fromRaw($rawUnit !== null ? (string)$rawUnit : null);
    }
}
