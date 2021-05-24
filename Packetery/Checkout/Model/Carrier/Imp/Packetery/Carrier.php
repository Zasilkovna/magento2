<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier\Imp\Packetery;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Packetery\Checkout\Model\Pricing;

class Carrier extends \Packetery\Checkout\Model\Carrier\AbstractCarrier
{
    /** @var bool  */
    protected $_isFixed = true;

    /** @var \Packetery\Checkout\Model\Carrier\Imp\Packetery\Brain */
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
