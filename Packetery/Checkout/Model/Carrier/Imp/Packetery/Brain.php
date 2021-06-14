<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier\Imp\Packetery;

use Packetery\Checkout\Model\Carrier\AbstractDynamicCarrier;
use Packetery\Checkout\Model\Carrier\Methods;

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
     */
    public function __construct(
        \Magento\Framework\App\Request\Http $httpRequest,
        \Packetery\Checkout\Model\Pricing\Service $pricingService,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Packetery\Checkout\Model\Carrier\Imp\Packetery\MethodSelect $methodSelect,
        \Packetery\Checkout\Model\ResourceModel\Carrier\CollectionFactory $carrierCollectionFactory
    ) {
        parent::__construct($httpRequest, $pricingService, $scopeConfig);
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

    /**
     * @return \Packetery\Checkout\Model\Carrier\Imp\Packetery\MethodSelect
     */
    public function getMethodSelect(): \Packetery\Checkout\Model\Carrier\Config\AbstractMethodSelect {
        return $this->methodSelect;
    }

    /**
     * @inheridoc
     */
    protected function getResolvableDestinationData(): array {
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
     * @param string $method
     * @param string $countryId
     * @param \Packetery\Checkout\Model\Carrier\AbstractDynamicCarrier|null $dynamicCarrier
     * @return bool
     */
    protected function availableForCollection(string $method, string $countryId, ?AbstractDynamicCarrier $dynamicCarrier = null): bool {
        $availableCountries = $this->getAvailableCountries([$method]);
        return in_array($countryId, $availableCountries) && parent::availableForCollection($method, $countryId, $dynamicCarrier);
    }

    /**
     * @param array $methods
     * @return array
     */
    public function getAvailableCountries(array $methods): array {
        if (!empty(array_diff($methods, [Methods::PICKUP_POINT_DELIVERY, Methods::ADDRESS_DELIVERY]))) {
            throw new \InvalidArgumentException('Some method is unsupported');
        }

        $result = [];

        if (in_array(Methods::ADDRESS_DELIVERY, $methods)) {
            $result = array_merge($result, array_keys($this->getResolvableDestinationData()[Methods::ADDRESS_DELIVERY]['countryBranchIds'] ?? []));
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
}
