<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier\Imp\Packetery;

use Packetery\Checkout\Model\Carrier\AbstractDynamicCarrier;
use Packetery\Checkout\Model\Carrier\Methods;
use Packetery\Checkout\Model\Carrier\VendorCodes;

class Brain extends \Packetery\Checkout\Model\Carrier\AbstractBrain
{
    /** @var \Packetery\Checkout\Model\Carrier\Imp\Packetery\MethodSelect */
    private $methodSelect;

    /** @var \Packetery\Checkout\Model\ResourceModel\Carrier\CollectionFactory */
    private $carrierCollectionFactory;

    /**
     * Brain constructor.
     *
     * @param \Magento\Framework\App\Request\Http $httpRequest
     * @param \Packetery\Checkout\Model\Pricing\Service $pricingService
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Packetery\Checkout\Model\Carrier\Imp\Packetery\MethodSelect $methodSelect
     * @param \Packetery\Checkout\Model\ResourceModel\Carrier\CollectionFactory $carrierCollectionFactory
     * @param \Packetery\Checkout\Model\Weight\Calculator $weightCalculator
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Framework\App\State $appState
     */
    public function __construct(
        \Magento\Framework\App\Request\Http $httpRequest,
        \Packetery\Checkout\Model\Pricing\Service $pricingService,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Packetery\Checkout\Model\Carrier\Imp\Packetery\MethodSelect $methodSelect,
        \Packetery\Checkout\Model\ResourceModel\Carrier\CollectionFactory $carrierCollectionFactory,
        \Packetery\Checkout\Model\Weight\Calculator $weightCalculator,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Framework\App\State $appState
    ) {
        parent::__construct($httpRequest, $pricingService, $scopeConfig, $weightCalculator, $rateResultFactory, $appState);
        $this->methodSelect = $methodSelect;
        $this->carrierCollectionFactory = $carrierCollectionFactory;
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
     * @inheridoc
     */
    protected static function getResolvableDestinationData(): array {
        return [
            Methods::ADDRESS_DELIVERY => [
                'countryBranchIds' => [
                    'CZ' => 106,
                    'SK' => 131,
                    'HU' => 4159,
                    'RO' => 4161,
                    'PL' => 4162,
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public static function getImplementedBranchIds(): array {
        return array_values(self::getResolvableDestinationData()[Methods::ADDRESS_DELIVERY]['countryBranchIds']);
    }

    /**
     * @param array $methods
     * @return array
     */
    public function getAvailableCountries(array $methods): array {
        $result = [];

        if (in_array(Methods::ADDRESS_DELIVERY, $methods)) {
            $result = array_merge($result, array_keys($this::getResolvableDestinationData()[Methods::ADDRESS_DELIVERY]['countryBranchIds'] ?? []));
        }

        if (in_array(Methods::PICKUP_POINT_DELIVERY, $methods)) {
            $fixedCountries = $this->getBaseCountries();

            $collection = $this->carrierCollectionFactory->create();
            $collection->forDeliveryMethod(Methods::PICKUP_POINT_DELIVERY);
            $countries = $collection->getColumnValues('country');

            $result = array_merge($result, array_unique(array_merge($fixedCountries, $countries)));
        }

        return $result;
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
            if ($id === $carrier->getCarrierId()) {
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
        $zpointTitle = 'Packeta internal pickup points';
        $zboxTitle = 'Packeta - Z-BOX';

        return [
            new VendorCarrier(
                1,
                VendorCodes::CZZPOINT,
                $zpointTitle,
                'CZ',
            ),
            new VendorCarrier(
                2,
                VendorCodes::CZZBOX,
                $zboxTitle,
                'CZ',
            ),
            new VendorCarrier(
                3,
                VendorCodes::CZALZABOX,
                'Packeta - AlzaBox',
                'CZ',
            ),
            new VendorCarrier(
                4,
                VendorCodes::SKZPOINT,
                $zpointTitle,
                'SK',
            ),
            new VendorCarrier(
                5,
                VendorCodes::SKZBOX,
                $zboxTitle,
                'SK',
            ),
            new VendorCarrier(
                6,
                VendorCodes::HUZPOINT,
                $zpointTitle,
                'HU',
            ),
            new VendorCarrier(
                7,
                VendorCodes::HUZBOX,
                $zboxTitle,
                'HU',
            ),
            new VendorCarrier(
                8,
                VendorCodes::ROZPOINT,
                $zpointTitle,
                'RO',
            ),
            new VendorCarrier(
                9,
                VendorCodes::ROZBOX,
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
