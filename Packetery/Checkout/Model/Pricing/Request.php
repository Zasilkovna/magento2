<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Pricing;

use Magento\Quote\Model\Quote\Address\RateRequest;

class Request
{
    /** @var \Magento\Quote\Model\Quote\Address\RateRequest */
    private $rateRequest;

    /** @var \Packetery\Checkout\Model\Carrier\AbstractCarrier */
    private $carrier;

    /**
     * Request constructor.
     *
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $rateRequest
     * @param \Packetery\Checkout\Model\Carrier\AbstractCarrier $carrier
     */
    public function __construct(RateRequest $rateRequest, \Packetery\Checkout\Model\Carrier\AbstractCarrier $carrier)
    {
        $this->rateRequest = $rateRequest;
        $this->carrier = $carrier;
    }

    /**
     * @return \Magento\Quote\Model\Quote\Address\RateRequest
     */
    public function getRateRequest(): RateRequest
    {
        return $this->rateRequest;
    }

    public function getCarrier(): \Packetery\Checkout\Model\Carrier\AbstractCarrier
    {
        return $this->carrier;
    }
}
