<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier\Imp\Packetery;

use Packetery\Checkout\Model\Carrier\Methods;

class Brain extends \Packetery\Checkout\Model\Carrier\AbstractBrain
{
    /** @var \Packetery\Checkout\Model\Config\Source\MethodSelect */
    private $methodSelect;

    /** @var \Packetery\Checkout\Model\Carrier\Imp\Packetery\CountrySelect */
    private $countrySelect;

    /**
     * Brain constructor.
     *
     * @param \Magento\Framework\App\Request\Http $httpRequest
     * @param \Packetery\Checkout\Model\Pricing\Service $pricingService
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Packetery\Checkout\Model\Config\Source\MethodSelect $methodSelect
     * @param \Packetery\Checkout\Model\Carrier\Imp\Packetery\CountrySelect $countrySelect
     */
    public function __construct(
        \Magento\Framework\App\Request\Http $httpRequest,
        \Packetery\Checkout\Model\Pricing\Service $pricingService,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Packetery\Checkout\Model\Config\Source\MethodSelect $methodSelect,
        \Packetery\Checkout\Model\Carrier\Imp\Packetery\CountrySelect $countrySelect
    )
    {
        parent::__construct($httpRequest, $pricingService, $scopeConfig);
        $this->methodSelect = $methodSelect;
        $this->countrySelect = $countrySelect;
    }

    /**
     * @param \Packetery\Checkout\Model\Carrier\AbstractCarrier $carrier
     * @return \Packetery\Checkout\Model\Carrier\Config\AbstractConfig
     */
    public function createConfig(\Packetery\Checkout\Model\Carrier\AbstractCarrier $carrier): \Packetery\Checkout\Model\Carrier\Config\AbstractConfig {
        return new Config($this->getConfigData($carrier->getCarrierCode(), $carrier->getStore()));
    }

    /**
     * @return \Packetery\Checkout\Model\Config\Source\MethodSelect
     */
    public function getMethodSelect(): \Packetery\Checkout\Model\Carrier\Config\AbstractMethodSelect
    {
        return $this->methodSelect;
    }

    /**
     * @return \Packetery\Checkout\Model\Carrier\Imp\Packetery\CountrySelect
     */
    public function getCountrySelect(): \Packetery\Checkout\Model\Carrier\Config\AbstractCountrySelect {
        return $this->countrySelect;
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
                ]
            ]
        ];
    }
}
