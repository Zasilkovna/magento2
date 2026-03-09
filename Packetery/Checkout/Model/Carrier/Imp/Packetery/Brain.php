<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier\Imp\Packetery;

use Packetery\Checkout\Model\Carrier\AbstractDynamicCarrier;
use Packetery\Checkout\Model\Carrier\VendorGroups;

class Brain extends \Packetery\Checkout\Model\Carrier\AbstractBrain
{
    /** @var \Packetery\Checkout\Model\Carrier\Imp\Packetery\MethodSelect */
    private $methodSelect;

    /**
     * @param \Magento\Framework\App\Request\Http $httpRequest
     * @param \Packetery\Checkout\Model\Pricing\Service $pricingService
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Packetery\Checkout\Model\Carrier\Imp\Packetery\MethodSelect $methodSelect
     * @param \Packetery\Checkout\Model\Weight\Calculator $weightCalculator
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Framework\App\State $appState
     */
    public function __construct(
        \Magento\Framework\App\Request\Http $httpRequest,
        \Packetery\Checkout\Model\Pricing\Service $pricingService,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Packetery\Checkout\Model\Carrier\Imp\Packetery\MethodSelect $methodSelect,
        \Packetery\Checkout\Model\Weight\Calculator $weightCalculator,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Framework\App\State $appState
    ) {
        parent::__construct($httpRequest, $pricingService, $scopeConfig, $weightCalculator, $rateResultFactory, $appState);
        $this->methodSelect = $methodSelect;
    }

    /**
     * @param \Packetery\Checkout\Model\Carrier\AbstractCarrier $carrier
     * @return \Packetery\Checkout\Model\Carrier\Config\AbstractConfig
     */
    public function createConfig(\Packetery\Checkout\Model\Carrier\AbstractCarrier $carrier): \Packetery\Checkout\Model\Carrier\Config\AbstractConfig {
        return new Config($this->getConfigData($carrier->getCarrierCode(), $carrier->getStore()));
    }

    public function createDynamicConfig(\Packetery\Checkout\Model\Carrier\Config\AbstractConfig $config, ?AbstractDynamicCarrier $dynamicCarrier = null): \Packetery\Checkout\Model\Carrier\Config\AbstractConfig {
        if ($dynamicCarrier === null) {
            return $config;
        }

        return new DynamicConfig(
            $config,
            $dynamicCarrier
        );
    }

    /**
     * @return \Packetery\Checkout\Model\Carrier\Imp\Packetery\MethodSelect
     */
    public function getMethodSelect(): \Packetery\Checkout\Model\Carrier\Config\AbstractMethodSelect {
        return $this->methodSelect;
    }

    /**
     * @param array $methods
     * @return array
     */
    public function getAvailableCountries(array $methods): array {
        return $this->getBaseCountries();
    }

    /**
     * @return string[]
     */
    public function getBaseCountries(): array {
        return ['CZ', 'SK', 'HU', 'RO'];
    }

    public function getDynamicCarrierById( ?int $id ): ?\Packetery\Checkout\Model\Carrier\AbstractDynamicCarrier {
        if ($id === null) {
            return null;
        }

        foreach ( $this->findResolvableDynamicCarriers() as $carrier ) {
            if ($id === $carrier->getDynamicCarrierId()) {
                return $carrier;
            }
        }

        return null;
    }

    public function validateDynamicCarrier( string $method, string $countryId, ?\Packetery\Checkout\Model\Carrier\AbstractDynamicCarrier $dynamicCarrier = null ): bool {
        if ($dynamicCarrier === null) {
            return true;
        }

        if ($dynamicCarrier->getCountryId() === $countryId && in_array($method, $dynamicCarrier->getMethods(), true)) {
            return true;
        }

        return false;
    }

    /**
     * @return \Packetery\Checkout\Model\Carrier\AbstractDynamicCarrier[]
     */
    public function findResolvableDynamicCarriers(): array {
        $zpointTitle = 'Packeta Pick-up Point';
        $zboxTitle = 'Packeta Z-BOX';

        return [
            new VendorCarrier(
                1,
                VendorGroups::ZPOINT,
                $zpointTitle,
                'CZ',
            ),
            new VendorCarrier(
                2,
                VendorGroups::ZBOX,
                $zboxTitle,
                'CZ',
            ),
            new VendorCarrier(
                4,
                VendorGroups::ZPOINT,
                $zpointTitle,
                'SK',
            ),
            new VendorCarrier(
                5,
                VendorGroups::ZBOX,
                $zboxTitle,
                'SK',
            ),
            new VendorCarrier(
                6,
                VendorGroups::ZPOINT,
                $zpointTitle,
                'HU',
            ),
            new VendorCarrier(
                7,
                VendorGroups::ZBOX,
                $zboxTitle,
                'HU',
            ),
            new VendorCarrier(
                8,
                VendorGroups::ZPOINT,
                $zpointTitle,
                'RO',
            ),
            new VendorCarrier(
                9,
                VendorGroups::ZBOX,
                $zboxTitle,
                'RO',
            ),
        ];
    }

    /**
     * @param string[] $methods
     * @return \Packetery\Checkout\Model\Carrier\AbstractDynamicCarrier[]
     */
    public function findConfigurableDynamicCarriers( string $country, array $methods ): array {
        $carriers = [];

        foreach ($this->findResolvableDynamicCarriers() as $carrier) {
            if ($country === $carrier->getCountryId() && array_intersect($carrier->getMethods(), $methods) !== []) {
                $carriers[] = $carrier;
            }
        }

        return $carriers;
    }
}
