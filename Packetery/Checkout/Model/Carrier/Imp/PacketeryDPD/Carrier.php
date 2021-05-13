<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier\Imp\PacketeryDPD;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Packetery\Checkout\Model\Carrier\AbstractCarrier;
use Packetery\Checkout\Model\Carrier\Config\AbstractConfig;
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

    protected function createConfig(): AbstractConfig
    {
        return new class($this) extends \Packetery\Checkout\Model\Carrier\Config\AbstractConfig {};
    }

    protected function getPacketeryCode(): string
    {
        return 'DPD';
    }

    public function getMethodSelect(): \Packetery\Checkout\Model\Carrier\Config\AbstractMethodSelect
    {
        return new \Packetery\Checkout\Model\Carrier\Imp\PacketeryDPD\MethodSelect();
    }

    public function getCountrySelect(): \Packetery\Checkout\Model\Carrier\Config\AbstractCountrySelect
    {
        return $this->brain->getCountrySelect();
    }
}
