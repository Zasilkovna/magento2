<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier\Imp\PacketeryDPD;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Packetery\Checkout\Model\Carrier\AbstractCarrier;
use Packetery\Checkout\Model\Pricing;

class Carrier extends AbstractCarrier implements CarrierInterface
{
    /** @var bool  */
    protected $_isFixed = true;

    /** @var \Packetery\Checkout\Model\Carrier\Imp\PacketeryDPD\Brain */
    protected $packeteryBrain;

    /**
     * {@inheritdoc}
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->packeteryBrain->isCollectionPossible($this, $request->getDestCountryId())) {
            return false;
        }

        $pricingRequest = new Pricing\Request($request, $this);
        $result = $this->packeteryBrain->collectRates($pricingRequest);
        if (!$result instanceof \Magento\Shipping\Model\Rate\Result) {
            return false;
        }

        return $result;
    }
}
