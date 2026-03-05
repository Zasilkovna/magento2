<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Payment;

use Packetery\Checkout\Model\Carrier\ShippingRateCode;
use Packetery\Checkout\Model\MaxCodResolver;

class MethodList
{
    /**
     * @var \Packetery\Checkout\Model\Carrier\Imp\Packetery\Carrier
     */
    private $packetery;

    /**
     * @var \Packetery\Checkout\Model\Pricing\Service
     */
    private $pricingService;

    /** @var MaxCodResolver */
    private $maxCodResolver;

    /**
     * @param \Packetery\Checkout\Model\Carrier\Imp\Packetery\Carrier $packetery
     * @param \Packetery\Checkout\Model\Pricing\Service $pricingService
     */
    public function __construct(
        \Packetery\Checkout\Model\Carrier\Imp\Packetery\Carrier $packetery,
        \Packetery\Checkout\Model\Pricing\Service $pricingService,
        MaxCodResolver $maxCodResolver
    ) {
        $this->packetery = $packetery;
        $this->pricingService = $pricingService;
        $this->maxCodResolver = $maxCodResolver;
    }

    /**
     * @param \Magento\Payment\Model\MethodList $subject
     * @param $availableMethods
     * @param \Magento\Quote\Model\Quote|null $quote
     * @return mixed|null
     */
    public function afterGetAvailableMethods(
        \Magento\Payment\Model\MethodList $subject,
        $availableMethods,
        ?\Magento\Quote\Model\Quote $quote = null
    ) {
        if ($quote === null) {
            return $availableMethods;
        }

        $shippingRateCode = (string) $quote->getShippingAddress()->getShippingMethod();
        if (!ShippingRateCode::isPacketery($shippingRateCode)) {
            return $availableMethods;
        }

        $countryId = (string) $quote->getShippingAddress()->getCountryId();
        $shippingRateCodeObject = ShippingRateCode::fromString($shippingRateCode);
        $methodCodeObject = $shippingRateCodeObject->getMethodCode();
        $relatedPricingRule = $this->pricingService->resolvePricingRule(
            $methodCodeObject->getMethod(),
            $countryId,
            $shippingRateCodeObject->getCarrierCode(),
            $methodCodeObject->getDynamicCarrierId()
        );

        if ($relatedPricingRule === null) {
            return $availableMethods;
        }

        $grandTotal = (float) $quote->getGrandTotal();
        $maxCod = $this->maxCodResolver->resolve($relatedPricingRule);
        $exceedsLimit = self::exceedsValueMaxLimit($grandTotal, $maxCod);

        $config = $this->packetery->getPacketeryConfig();
        foreach ($availableMethods as $key => $method) {
            $isCodPaymentMethod = in_array($method->getCode(), $config->getCodMethods(), true);
            if($isCodPaymentMethod && $exceedsLimit) {
                unset($availableMethods[$key]);
            }
        }

        return $availableMethods;
    }

    /**
     * @param float $value
     * @param float|null $maxLimit
     * @return bool
     */
    public static function exceedsValueMaxLimit(float $value, ?float $maxLimit): bool {
        $exceedsLimit = false;
        if ($maxLimit !== null) {
            $exceedsLimit = $value > $maxLimit;
        }

        return $exceedsLimit;
    }
}
