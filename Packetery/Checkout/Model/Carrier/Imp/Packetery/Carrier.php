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
     * {@inheritdoc}
     */
    protected function createConfig(): \Packetery\Checkout\Model\Carrier\Config\AbstractConfig
    {
        return new Config($this);
    }

    /**
     * {@inheritdoc}
     */
    public function getMethodSelect(): \Packetery\Checkout\Model\Carrier\Config\AbstractMethodSelect
    {
        return $this->brain->getMethodSelect();
    }

    /**
     * @return string
     */
    protected function getPacketeryCode(): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getCountrySelect(): \Packetery\Checkout\Model\Carrier\Config\AbstractCountrySelect
    {
        return $this->brain->getCountrySelect();
    }
}
