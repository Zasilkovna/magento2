<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier;

use Packetery\Checkout\Model\Carrier\Imp\Packetery\VendorCarrier;
use Packetery\Checkout\Model\HybridCarrier;

class Facade
{
    /** @var \Magento\Shipping\Model\CarrierFactory */
    private $carrierFactory;

    /** @var \Magento\Shipping\Model\Config */
    private $shippingConfig;

    /**
     * @param \Magento\Shipping\Model\CarrierFactory $carrierFactory
     * @param \Magento\Shipping\Model\Config $shippingConfig
     */
    public function __construct(
        \Magento\Shipping\Model\CarrierFactory $carrierFactory,
        \Magento\Shipping\Model\Config $shippingConfig
    ) {
        $this->carrierFactory = $carrierFactory;
        $this->shippingConfig = $shippingConfig;
    }

    /**
     * @param string $carrierName
     * @param string $carrierCode
     * @param int|null $carrierId
     * @throws \Exception
     */
    public function updateCarrierName(string $carrierName, string $carrierCode, ?int $carrierId = null): void {
        $carrier = $this->getMagentoCarrier($carrierCode);
        $dynamicCarrier = $this->getDynamicCarrier($carrier, $carrierId);

        if ($dynamicCarrier !== null) {
            $carrier->getPacketeryBrain()->updateDynamicCarrierName($carrierName, $dynamicCarrier);
            return;
        }

        throw new \InvalidArgumentException('Not implemented');
    }

    /**
     * @param string $carrierCode
     * @param int|null $carrierId
     * @param string $method
     * @param string $country
     * @return \Packetery\Checkout\Model\HybridCarrier
     */
    public function createHybridCarrier(string $carrierCode, ?int $carrierId, string $method, string $country): HybridCarrier {
        $cache = [];
        return $this->createHybridCarrierCached($cache, $carrierCode, $carrierId, $method, $country);
    }

    /**
     * @param array $cache
     * @param string $carrierCode
     * @param int|null $carrierId
     * @param string $method
     * @param string $country
     * @return \Packetery\Checkout\Model\HybridCarrier
     */
    public function createHybridCarrierCached(array &$cache, string $carrierCode, ?int $carrierId, string $method, string $country): HybridCarrier {
        $cache['carriers'] = $cache['carriers'] ?? [];
        $cache['dynamicCarriers'] = $cache['dynamicCarriers'] ?? [];

        if (!array_key_exists($carrierCode, $cache['carriers'])) {
            $cache['carriers'][$carrierCode] = $this->getMagentoCarrier($carrierCode);
        }
        $carrier = $cache['carriers'][$carrierCode];

        $dynamicCarrierCode = $carrierCode . '_' . ( $carrierId ?? '' );
        if (!array_key_exists($dynamicCarrierCode, $cache['dynamicCarriers'])) {
            $cache['dynamicCarriers'][$dynamicCarrierCode] = $this->getDynamicCarrier($carrier, $carrierId);
        }
        $dynamicCarrier = $cache['dynamicCarriers'][$dynamicCarrierCode];

        if ($dynamicCarrier !== null) {
            return HybridCarrier::fromAbstractDynamic($carrier, $dynamicCarrier, $method, $country);
        }

        $vendorCodesOptions = [];
        if ($carrier instanceof \Packetery\Checkout\Model\Carrier\Imp\Packetery\Carrier) {
            $dynamicCarriers = $carrier->getPacketeryBrain()->findConfigurableDynamicCarriers($country, [$method]);
            $vendorCodesOptions = $this->getVendorCodesOptions($dynamicCarriers);
        }

        return HybridCarrier::fromAbstract($carrier, $method, $country, $vendorCodesOptions);
    }

    /**
     * @param \Packetery\Checkout\Model\Carrier\AbstractDynamicCarrier[] $carriers
     * @return string[]
     */
    public function getVendorCodesOptions(array $carriers): array {
        $vendorCodesOptions = [];
        foreach ($carriers as $carrier) {
            if ($carrier instanceof VendorCarrier) {
                $vendorCodesOptions[] = [
                    'value' => $carrier->getGroup(),
                    'label' => VendorGroups::getLabel($carrier->getGroup()),
                ];
            }
        }

        return $vendorCodesOptions;
    }

    /**
     * @param \Packetery\Checkout\Model\Carrier\AbstractDynamicCarrier[] $carriers
     * @return string[]
     */
    public function getVendorGroups(array $carriers): array {
        $vendorGroups = [];
        foreach ($carriers as $carrier) {
            if ($carrier instanceof VendorCarrier) {
                $vendorGroups[] = $carrier->getGroup();
            }
        }

        return $vendorGroups;
    }

    /**
     * @param string $carrierCode
     * @param $carrierId
     * @return bool
     */
    public function isDynamicCarrier(string $carrierCode, $carrierId): bool {
        $carrier = $this->getMagentoCarrier($carrierCode);
        $dynamicCarrier = $this->getDynamicCarrier($carrier, (is_numeric($carrierId) ? (int)$carrierId : null));

        if ($dynamicCarrier !== null) {
            return true;
        }

        return false;
    }

    /**
     * @return AbstractCarrier[]
     */
    public function getPacketeryAbstractCarriers(): array {
        $carriers = [];

        foreach ($this->shippingConfig->getAllCarriers() as $carrier) {
            if ($carrier instanceof AbstractCarrier) {
                $carriers[] = $carrier;
            }
        }

        return $carriers;
    }

    /**
     * @return array
     */
    public function getAllAvailableCountries(): array {
        $countries = [];

        foreach ($this->getPacketeryAbstractCarriers() as $packeteryAbstractCarrier) {
            $carrierMethods = Methods::getAll();
            $countries = array_merge($countries, $packeteryAbstractCarrier->getPacketeryBrain()->getAvailableCountries($carrierMethods));
        }

        return array_unique($countries);
    }

    /**
     * @param int $carrierId
     * @return \Packetery\Checkout\Model\Carrier\AbstractDynamicCarrier|null
     */
    private function getDynamicCarrier(AbstractCarrier $carrier, ?int $carrierId): ?AbstractDynamicCarrier {
        return $carrier->getPacketeryBrain()->getDynamicCarrierById($carrierId);
    }

    /**
     * @param string $carrierCode
     * @return \Packetery\Checkout\Model\Carrier\AbstractCarrier
     */
    public function getMagentoCarrier(string $carrierCode): AbstractCarrier {
        return $this->carrierFactory->get($carrierCode);
    }

    /**
     * @return array
     */
    public static function getAllImplementedBranchIds(): array {
        $branchIds = [];
        $classNames = self::getAllBrainClasses();

        foreach ($classNames as $className) {
            $branchIds[] = $className::getImplementedBranchIds();
        }

        return array_merge([], ...$branchIds);
    }

    /**
     * @return array<class-string<\Packetery\Checkout\Model\Carrier\AbstractBrain>>
     */
    public static function getAllBrainClasses(): array {
        $classNames = [];
        $dirs = glob(__DIR__ . '/Imp/*', GLOB_ONLYDIR|GLOB_NOSORT);

        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }

            $name = basename($dir);
            $classNames[] = '\\Packetery\\Checkout\\Model\\Carrier\\Imp\\' . $name . '\\Brain';
        }

        return $classNames;
    }

    /**
     * @return string[]
     */
    public static function getAllCarrierCodes(): array {
        $carrierCodes = [];
        /** @var \Packetery\Checkout\Model\Carrier\AbstractBrain[] $classNames */
        $classNames = self::getAllBrainClasses();

        foreach ($classNames as $className) {
            $carrierCodes[] = $className::getCarrierCodeStatic();
        }

        return $carrierCodes;
    }

    /**
     * @param string $carrierCode
     * @param int|null $carrierId
     * @return float|null
     */
    public function getMaxWeight(string $carrierCode, ?int $carrierId): ?float {
        $carrier = $this->getMagentoCarrier($carrierCode);
        $dynamicCarrier = $this->getDynamicCarrier($carrier, $carrierId);
        return $carrier->getPacketeryDynamicConfig($dynamicCarrier)->getMaxWeight();
    }
}
