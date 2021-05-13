<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier;

use Magento\Shipping\Model\Rate\Result;
use Packetery\Checkout\Model\Pricing\Request;

/**
 * Use this service to extend custom carriers with new logic that is using dependencies. Good for avoiding constructor hell.
 */
class Brain
{
    /** @var \Magento\Framework\App\Request\Http  */
    protected $httpRequest;

    /** @var \Packetery\Checkout\Model\Pricing\Service  */
    protected $pricingService;

    /**
     * CarrierBrain constructor.
     *
     * @param \Magento\Framework\App\Request\Http $httpRequest
     * @param \Packetery\Checkout\Model\Pricing\Service $pricingService
     */
    public function __construct(
        \Magento\Framework\App\Request\Http $httpRequest,
        \Packetery\Checkout\Model\Pricing\Service $pricingService
    )
    {
        $this->httpRequest = $httpRequest;
        $this->pricingService = $pricingService;
    }

    /**
     * @param \Packetery\Checkout\Model\Pricing\Request $pricingRequest
     * @return \Magento\Shipping\Model\Rate\Result|null
     */
    public function collectRates(Request $pricingRequest): ?Result
    {
        return $this->pricingService->collectRates($pricingRequest);
    }

    /**
     * @param \Packetery\Checkout\Model\Carrier\AbstractCarrier $carrier
     * @param string $countryId
     * @return bool
     */
    public function isCollectionPossible(AbstractCarrier $carrier, string $countryId)
    {
        if ($this->httpRequest->getModuleName() == AbstractCarrier::MULTI_SHIPPING_MODULE_NAME) {
            return false;
        }

        $carrierConfig = $carrier->getConfig();
        if (!$carrierConfig->isActive()) {
            return false;
        }

        if (!$carrierConfig->hasSpecificCountryAllowed($countryId)) {
            return false;
        }

        return true;
    }
}
