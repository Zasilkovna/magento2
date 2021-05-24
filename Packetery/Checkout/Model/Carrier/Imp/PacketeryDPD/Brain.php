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
     * Brain constructor.
     *
     * @param \Magento\Framework\App\Request\Http $httpRequest
     * @param \Packetery\Checkout\Model\Pricing\Service $pricingService
     * @param \Packetery\Checkout\Model\Carrier\Imp\PacketeryDPD\CountrySelect $countrySelect
     * @param \Packetery\Checkout\Model\Carrier\Imp\PacketeryDPD\MethodSelect $methodSelect
     */
    public function __construct(
        \Magento\Framework\App\Request\Http $httpRequest,
        \Packetery\Checkout\Model\Pricing\Service $pricingService,
        CountrySelect $countrySelect,
        MethodSelect $methodSelect
    )
    {
        parent::__construct($httpRequest, $pricingService);
        $this->countrySelect = $countrySelect;
        $this->methodSelect = $methodSelect;
    }

    public function createConfig(\Packetery\Checkout\Model\Carrier\AbstractCarrier $carrier): \Packetery\Checkout\Model\Carrier\Config\AbstractConfig {
        return new Config($carrier);
    }

    /**
     * @return \Packetery\Checkout\Model\Carrier\Imp\PacketeryDPD\CountrySelect
     */
    public function getCountrySelect(): \Packetery\Checkout\Model\Carrier\Config\AbstractCountrySelect
    {
        return $this->countrySelect;
    }

    public function getMethodSelect(): \Packetery\Checkout\Model\Carrier\Config\AbstractMethodSelect {
        return $this->methodSelect;
    }

    public function getPacketeryCode(): string
    {
        return 'DPD';
    }

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
