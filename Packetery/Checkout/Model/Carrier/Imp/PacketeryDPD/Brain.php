<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier\Imp\PacketeryDPD;

use Packetery\Checkout\Model\Carrier\Methods;

class Brain extends \Packetery\Checkout\Model\Carrier\AbstractBrain
{
    /** @var \Packetery\Checkout\Model\Carrier\Imp\PacketeryDPD\CountrySelect */
    private $countrySelect;

    /** @var \Packetery\Checkout\Model\Carrier\Imp\PacketeryDPD\MethodSelect */
    private $methodSelect;

    /**
     * @param \Magento\Framework\App\Request\Http $httpRequest
     * @param \Packetery\Checkout\Model\Pricing\Service $pricingService
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Packetery\Checkout\Model\Carrier\Imp\PacketeryDPD\CountrySelect $countrySelect
     * @param \Packetery\Checkout\Model\Carrier\Imp\PacketeryDPD\MethodSelect $methodSelect
     */
    public function __construct(
        \Magento\Framework\App\Request\Http $httpRequest,
        \Packetery\Checkout\Model\Pricing\Service $pricingService,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        CountrySelect $countrySelect,
        MethodSelect $methodSelect
    )
    {
        parent::__construct($httpRequest, $pricingService, $scopeConfig);
        $this->countrySelect = $countrySelect;
        $this->methodSelect = $methodSelect;
    }

    /**
     * @param \Packetery\Checkout\Model\Carrier\AbstractCarrier $carrier
     * @return \Packetery\Checkout\Model\Carrier\Config\AbstractConfig
     */
    public function createConfig(\Packetery\Checkout\Model\Carrier\AbstractCarrier $carrier): \Packetery\Checkout\Model\Carrier\Config\AbstractConfig {
        return new Config($this->getConfigData($carrier->getCarrierCode(), $carrier->getStore()));
    }

    /**
     * @return \Packetery\Checkout\Model\Carrier\Imp\PacketeryDPD\CountrySelect
     */
    public function getCountrySelect(): \Packetery\Checkout\Model\Carrier\Config\AbstractCountrySelect
    {
        return $this->countrySelect;
    }

    /**
     * @return \Packetery\Checkout\Model\Carrier\Config\AbstractMethodSelect
     */
    public function getMethodSelect(): \Packetery\Checkout\Model\Carrier\Config\AbstractMethodSelect {
        return $this->methodSelect;
    }

    /**
     * @return array
     */
    protected function getResolvableDestinationData(): array {
        return [
            Methods::ADDRESS_DELIVERY => [
                'countryBranchIds' => [
                    'AT' => 6830,
                    'HR' => 4646,
                    'LU' => 4834,
                    'RO' => 836,
                    'SI' => 4949,
                ]
            ]
        ];
    }
}
