<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier\Config;

/**
 * Represents config of all internal and external carriers
 */
abstract class AbstractConfig
{
    /** @var \Packetery\Checkout\Model\Carrier\AbstractCarrier  */
    protected $carrier;

    /**
     * AbstractConfig constructor.
     *
     * @param \Packetery\Checkout\Model\Carrier\AbstractCarrier $carrier
     */
    public function __construct(\Packetery\Checkout\Model\Carrier\AbstractCarrier $carrier)
    {
        $this->carrier = $carrier;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->carrier->getConfigFlag('active');
    }

    /**
     * @return false|\Magento\Framework\Phrase|string
     */
    public function getTitle()
    {
        return ($this->carrier->getConfigData('title') ?: __("Packeta"));
    }

    /**
     * @return float|null
     */
    public function getDefaultPrice(): ?float
    {
        $value = $this->carrier->getConfigData('default_price');
        return (is_numeric($value) ? (float)$value : null);
    }

    /** kilos
     * @return float|null
     */
    public function getMaxWeight(): ?float
    {
        $value = $this->carrier->getConfigData('max_weight');
        return (is_numeric($value) ? (float)$value : null);
    }

    /**
     * @return int|null
     */
    protected function getFreeShippingEnable(): ?int
    {
        $value = $this->carrier->getConfigData('free_shipping_enable');
        return (is_numeric($value) ? (int)$value : null);
    }

    /** Order value threshold
     * @return float|null
     */
    public function getFreeShippingThreshold(): ?float
    {
        if ($this->getFreeShippingEnable() === 1) {
            $value = $this->carrier->getConfigData('free_shipping_subtotal');
            return (is_numeric($value) ? (float)$value : null);
        }

        return null;
    }

    /** 1 => Specific countries
     *  0 => All countries
     * @return int
     */
    public function getApplicableCountries(): int
    {
        $value = $this->carrier->getConfigData('sallowspecific'); // "Use system value" resolves in 0
        return (int)$value;
    }

    /** Collection of allowed countries
     * @return array
     */
    public function getSpecificCountries(): array
    {
        $value = $this->carrier->getConfigData('specificcountry');
        return (is_string($value) ? explode(',', $value) : []);
    }

    /**
     * @param string $countryId
     * @return bool
     */
    public function hasSpecificCountryAllowed(string $countryId): bool
    {
        if ($this->getApplicableCountries() === 1) {
            $countries = $this->getSpecificCountries();
            return empty($countries) || in_array($countryId, $countries);
        }

        if ($this->getApplicableCountries() === 0) {
            $countrySelect = $this->carrier->getPacketeryBrain()->getCountrySelect();
            return $countrySelect->getLabelByValue($countryId) !== null;
        }

        return false;
    }

    /**
     * @return \Packetery\Checkout\Model\Carrier\Config\AllowedMethods
     */
    private function getAllowedMethods(): AllowedMethods
    {
        $value = $this->carrier->getConfigData('allowedMethods');
        $methods = (is_string($value) ? explode(',', $value) : []);
        return new AllowedMethods($methods);
    }

    /** Allowed methods that are considered in rate collection
     * @return \Packetery\Checkout\Model\Carrier\Config\AllowedMethods
     */
    public function getFinalAllowedMethods(): AllowedMethods {
        $allowedMethods = $this->getAllowedMethods();
        if (empty($allowedMethods->toArray())) {
            return new AllowedMethods($this->carrier->getPacketeryBrain()->getMethodSelect()->getMethods());
        }

        return $allowedMethods;
    }
}
