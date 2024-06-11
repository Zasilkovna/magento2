<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model;

use Packetery\Checkout\Model\Carrier\MethodCode;
use Packetery\Checkout\Model\Misc\ComboPhrase;

/**
 * Merges dynamic (feed) carrier data structure and Magento fixed carrier data structure
 */
class HybridCarrier extends \Magento\Framework\DataObject
{
    /**
     * @param \Packetery\Checkout\Model\Carrier\AbstractCarrier $carrier
     * @param \Packetery\Checkout\Model\Carrier\AbstractDynamicCarrier $dynamicCarrier
     * @param string $method
     * @param string $country
     * @return \Packetery\Checkout\Model\HybridCarrier
     */
    public static function fromAbstractDynamic(\Packetery\Checkout\Model\Carrier\AbstractCarrier $carrier, \Packetery\Checkout\Model\Carrier\AbstractDynamicCarrier $dynamicCarrier, string $method, string $country)
    {
        $hybridCarrier = new self();
        $hybridCarrier->setData('carrier_code', $carrier->getCarrierCode());
        $hybridCarrier->setData('carrier_id', $dynamicCarrier->getDynamicCarrierId());
        $hybridCarrier->setData('name', $dynamicCarrier->getName());
        $hybridCarrier->setData('carrier_name', $dynamicCarrier->getFinalCarrierName());
        $hybridCarrier->setData('country', $country);
        $hybridCarrier->setData('method', $method);
        $hybridCarrier->setData('method_code', (new MethodCode($method, $dynamicCarrier->getDynamicCarrierId()))->toString());
        $hybridCarrier->setData('vendor_groups_options', []);

        return $hybridCarrier;
    }

    /**
     * @param \Packetery\Checkout\Model\Carrier\AbstractCarrier $carrier
     * @param string $method
     * @param string $country
     * @param array $vendorCodeOptions
     * @return static
     */
    public static function fromAbstract(\Packetery\Checkout\Model\Carrier\AbstractCarrier $carrier, string $method, string $country, array $vendorCodeOptions): self
    {
        $hybridCarrier = new self();
        $hybridCarrier->setData('carrier_code', $carrier->getCarrierCode());
        $hybridCarrier->setData('carrier_id');

        $postfix = '';
        if (\Packetery\Checkout\Model\Carrier\Methods::isAnyAddressDelivery($method)) {
            $postfix = 'HD';
        }
        if (\Packetery\Checkout\Model\Carrier\Methods::PICKUP_POINT_DELIVERY === $method) {
            $postfix = 'pickup points';
        }

        $hybridCarrier->setData('name', "$country {$carrier->getPacketeryConfig()->getTitle()} $postfix");
        $hybridCarrier->setData('carrier_name', $carrier->getPacketeryConfig()->getTitle());
        $hybridCarrier->setData('country', $country);
        $hybridCarrier->setData('method', $method);
        $hybridCarrier->setData('method_code', (new MethodCode($method, null))->toString());
        $hybridCarrier->setData('vendor_groups_options', $vendorCodeOptions);

        return $hybridCarrier;
    }

    /**
     * @param \Packetery\Checkout\Model\Pricingrule|null $pricingrule
     * @return string|\Packetery\Checkout\Model\Misc\ComboPhrase|null
     */
    public function getFieldsetTitle(?Pricingrule $pricingrule = null)
    {
        if ($pricingrule !== null) {
            $tags = [];

            if ($pricingrule->getEnabled()) {
                $tags[] = new ComboPhrase(['[', __('Enabled'), ']']);
            }

            return new ComboPhrase($tags + ['name' => $this->getData('name')], ' ');
        }

        return $this->getData('name');
    }

    /**
     * @return string
     */
    public function getCarrierCode(): string
    {
        return $this->getData('carrier_code');
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->getData('method');
    }

    /**
     * @return string
     */
    public function getMethodCode(): string
    {
        return $this->getData('method_code');
    }

    /**
     * @return int|null
     */
    public function getCarrierId(): ?int
    {
        $value = $this->getData('carrier_id');

        return (is_numeric($value) ? (int)$value : null);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return (string)$this->getData('name');
    }

    /**
     * @return string
     */
    public function getCarrierName(): string
    {
        return (string)$this->getData('carrier_name');
    }

    /**
     * @return string
     */
    public function getFinalCarrierName(): string
    {
        return ($this->getCarrierName() ?: $this->getName());
    }

    /**
     * @return string
     */
    public function getCountry(): string
    {
        return (string)$this->getData('country');
    }

    /**
     * @return bool
     */
    public function getDeleted(): bool
    {
        return (bool)$this->getData('deleted');
    }

    /**
     * @return array
     */
    public function getVendorGroupsOptions(): array
    {
        return $this->getData('vendor_groups_options');
    }

    /**
     * @return string[]
     */
    public function getVendorCodesOptionsValues(): array
    {
        return array_map(
            static function (array $options): string {
                return $options['value'];
            },
            $this->getVendorGroupsOptions()
        );
    }

    public function hasVendorGroupsOptions(): bool
    {
        return !empty($this->getVendorGroupsOptions());
    }

    public function hasNonInteractableVendorGroupsOptions(): bool
    {
        return count($this->getVendorGroupsOptions()) <= 2;
    }
}
