<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier\Imp\PacketeryDPD;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Packetery\Checkout\Model\Carrier\AbstractCarrier;
use Packetery\Checkout\Model\Carrier\Config\AbstractConfig;
use Packetery\Checkout\Model\Carrier\Methods;
use Packetery\Checkout\Model\Pricing;

class Carrier extends AbstractCarrier implements CarrierInterface
{
    /** @var bool  */
    protected $_isFixed = true;

    /** @var \Packetery\Checkout\Model\Carrier\Imp\PacketeryDPD\Brain */
    protected $brain;

    /**
     * {@inheritdoc}
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->brain->isCollectionPossible($this, $request->getDestCountryId())) {
            return false;
        }

        $pricingRequest = new Pricing\Request($request, $this);
        $result = $this->brain->collectRates($pricingRequest);
        if (!$result instanceof \Magento\Shipping\Model\Rate\Result) {
            return false;
        }

        return $result;
    }

    /**
     * @return Config
     */
    protected function createConfig(): AbstractConfig
    {
        return new Config($this);
    }

    protected function getPacketeryCode(): string
    {
        return 'DPD';
    }

    /**
     * @return \Packetery\Checkout\Model\Carrier\Imp\PacketeryDPD\MethodSelect
     */
    public function getMethodSelect(): \Packetery\Checkout\Model\Carrier\Config\AbstractMethodSelect
    {
        return $this->brain->getMethodSelect();
    }

    /**
     * @return \Packetery\Checkout\Model\Carrier\Imp\PacketeryDPD\CountrySelect
     */
    public function getCountrySelect(): \Packetery\Checkout\Model\Carrier\Config\AbstractCountrySelect
    {
        return $this->brain->getCountrySelect();
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
