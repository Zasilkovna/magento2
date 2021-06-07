<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier;

class Facade
{
    /** @var \Packetery\Checkout\Model\ResourceModel\Carrier\CollectionFactory */
    private $dynamicCarrierCollectionFactory;

    /** @var \Magento\Shipping\Model\CarrierFactory */
    private $carrierFactory;

    /**
     * @param \Packetery\Checkout\Model\ResourceModel\Carrier\CollectionFactory $dynamicCarrierCollectionFactory
     * @param \Magento\Shipping\Model\CarrierFactory $carrierFactory
     */
    public function __construct(\Packetery\Checkout\Model\ResourceModel\Carrier\CollectionFactory $dynamicCarrierCollectionFactory, \Magento\Shipping\Model\CarrierFactory $carrierFactory) {
        $this->dynamicCarrierCollectionFactory = $dynamicCarrierCollectionFactory;
        $this->carrierFactory = $carrierFactory;
    }

    public function getPublicName(string $carrierCode, ?int $carrierId = null): string {
        $this->assertValidIdentifiers($carrierCode, $carrierId);

        if ($this->isDynamicCarrier($carrierCode, $carrierId)) {
            $dynamicCarrier = $this->getDynamicCarrier($carrierId);
            return $dynamicCarrier->getFinalCarrierName();
        }

        $carrier = $this->getMagentoCarrier($carrierCode);
        return $carrier->getPacketeryConfig()->getTitle();
    }

    public function updateCarrierName(string $carrierName, string $carrierCode, ?int $carrierId = null): void {
        $this->assertValidIdentifiers($carrierCode, $carrierId);

        if ($this->isDynamicCarrier($carrierCode, $carrierId)) {
            $dynamicCarrier = $this->getDynamicCarrier($carrierId);
            $dynamicCarrier->setData('carrier_name', $carrierName);
            $dynamicCarrier->save();
            return;
        }

        $carrier = $this->getMagentoCarrier($carrierCode);
        $carrier->setData('title', $carrierName); // todo rm or fix
    }

    private function assertValidIdentifiers(string $carrierCode, $carrierId): void {
        $isDynamicWrapper = $carrierCode === \Packetery\Checkout\Model\Carrier\Imp\PacketeryPacketaDynamic\Brain::getCarrierCodeStatic();
        if ($isDynamicWrapper && is_numeric($carrierId)) {
            return;
        }

        if (is_numeric($carrierId)) {
            throw new \Exception('Invalid identifiers. Missing or unexpected code.');
        }
    }

    public function isDynamicCarrier(string $carrierCode, $carrierId): bool {
        $isDynamicWrapper = $carrierCode === \Packetery\Checkout\Model\Carrier\Imp\PacketeryPacketaDynamic\Brain::getCarrierCodeStatic();
        if ($isDynamicWrapper && is_numeric($carrierId)) {
            return true;
        }

        return false;
    }

    private function getDynamicCarrier(int $carrierId): \Packetery\Checkout\Model\Carrier {
        return $this->dynamicCarrierCollectionFactory->create()->getItemByColumnValue('carrier_id', $carrierId);
    }

    private function getMagentoCarrier(string $carrierCode): \Packetery\Checkout\Model\Carrier\AbstractCarrier {
        return $this->carrierFactory->get($carrierCode);
    }
}
