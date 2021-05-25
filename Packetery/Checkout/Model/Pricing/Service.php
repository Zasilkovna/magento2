<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Pricing;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;
use Packetery\Checkout\Model\Carrier\AbstractBrain;
use Packetery\Checkout\Model\Carrier\Config\AbstractConfig;
use Packetery\Checkout\Model\Pricingrule;

/**
 * Do not inject any Carrier related services due to dependency circulation
 */
class Service
{
    /** @var \Packetery\Checkout\Model\ResourceModel\Pricingrule\CollectionFactory  */
    private $pricingRuleCollectionFactory;

    /** @var \Packetery\Checkout\Model\ResourceModel\Weightrule\CollectionFactory  */
    private $weightRuleCollectionFactory;

    /** @var \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory  */
    private $rateMethodFactory;

    /** @var \Magento\Shipping\Model\Rate\ResultFactory  */
    private $rateResultFactory;

    /**
     * @param \Packetery\Checkout\Model\ResourceModel\Pricingrule\CollectionFactory $pricingRuleCollectionFactory
     * @param \Packetery\Checkout\Model\ResourceModel\Weightrule\CollectionFactory $weightRuleCollectionFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     */
    public function __construct
    (
        \Packetery\Checkout\Model\ResourceModel\Pricingrule\CollectionFactory $pricingRuleCollectionFactory,
        \Packetery\Checkout\Model\ResourceModel\Weightrule\CollectionFactory $weightRuleCollectionFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
    ) {
        $this->pricingRuleCollectionFactory = $pricingRuleCollectionFactory;
        $this->weightRuleCollectionFactory = $weightRuleCollectionFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->rateResultFactory = $rateResultFactory;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request
     * @param string $carrierCode
     * @param \Packetery\Checkout\Model\Carrier\Config\AbstractConfig $carrierConfig
     * @param \Packetery\Checkout\Model\Carrier\AbstractBrain $carrierBrain
     * @return \Magento\Shipping\Model\Rate\Result|null
     */
    public function collectRates(RateRequest $request, string $carrierCode, AbstractConfig $carrierConfig, AbstractBrain $carrierBrain): ?Result
    {
        $result = $this->rateResultFactory->create();

        $allowedMethods = $carrierConfig->getFinalAllowedMethods();

        $weightTotal = $request->getPackageWeight();
        $weightMax = $carrierConfig->getMaxWeight();

        if ($this->hasValidWeight($weightTotal, $weightMax) === false) {
            return null;
        }

        $methods = $allowedMethods->toArray();

        foreach ($methods as $allowedMethod) {
            if ($allowedMethod !== \Packetery\Checkout\Model\Carrier\Methods::PICKUP_POINT_DELIVERY) {
                $branchId = $carrierBrain->resolvePointId($allowedMethod, $request->getDestCountryId());
                if ($branchId === null) {
                    continue;
                }
            }

            $pricingRule = $this->resolvePricingRule($allowedMethod, $request->getDestCountryId());
            $price = $this->resolvePrice($request, $carrierConfig, $pricingRule);
            $method = $this->createRateMethod(
                $allowedMethod,
                $carrierCode,
                $carrierConfig->getTitle(),
                $carrierBrain->getMethodSelect()->getLabelByValue($allowedMethod),
                $price
            );
            $result->append($method);
        }

        return $result;
    }

    /**
     * @param float $weightTotal
     * @param float|null $weightMax
     * @return bool
     */
    private function hasValidWeight(float $weightTotal, ?float $weightMax): bool
    {
        return is_numeric($weightMax) && $weightTotal <= $weightMax;
    }

    /**
     * @param string $method
     * @param string $destCountryId
     * @return \Packetery\Checkout\Model\Pricingrule|null
     */
    public function resolvePricingRule(string $method, string $destCountryId): ?Pricingrule
    {
        $pricingRuleCollection = $this->pricingRuleCollectionFactory->create();
        $pricingRuleCollection->addFilter('method', $method);
        $pricingRuleCollection->addFilter('country_id', $destCountryId); // iso 2
        $first = ($pricingRuleCollection->getFirstRecord() ?: null);

        return $first;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request
     * @param \Packetery\Checkout\Model\Carrier\Config\AbstractConfig $config
     * @param \Packetery\Checkout\Model\Pricingrule|null $pricingRule
     * @return float
     */
    protected function resolvePrice(RateRequest $request, AbstractConfig $config, ?Pricingrule $pricingRule): float
    {
        $result = null;
        $weightTotal = (float)$request->getPackageWeight();
        $priceTotal = (float)$request->getPackageValue();

        $freeShipping = $this->getFreeShippingThreshold($pricingRule, $config->getFreeShippingThreshold());

        if ($freeShipping !== null && $freeShipping <= $priceTotal) {
            return 0;
        }

        $weightRules = null;
        if ($pricingRule) {
            $weightRules = $this->getWeightRulesByPricingRule($pricingRule);
        }

        if (!empty($weightRules)) {
            $result = $this->resolveWeightedPrice($weightRules, $weightTotal, $config->getMaxWeight());
        }

        if ($result === null) {
            $result = $config->getDefaultPrice();
        }

        return $result;
    }

    /**
     * @param \Packetery\Checkout\Model\Weightrule[] $weightRules
     * @param float $weightTotal
     * @return float|null
     */
    protected function resolveWeightedPrice(array $weightRules, float $weightTotal, ?float $fallbackWeight = null): ?float
    {
        $minWeight = null;
        $price = null;

        foreach ($weightRules as $rule) {
            $ruleMaxWeight = $rule->getMaxWeight();
            $rulePrice = $rule->getPrice();

            if ($ruleMaxWeight === null) {
                $ruleMaxWeight = $fallbackWeight;
            }

            $relevant = $weightTotal <= $ruleMaxWeight;
            if ($relevant === false) {
                continue;
            }

            if ($minWeight === null || $minWeight > $ruleMaxWeight) {
                $minWeight = $ruleMaxWeight;
                $price = $rulePrice;
            }
        }

        return $price;
    }

    /**
     * @param string $packeteryMethod
     * @param string $carrierCode
     * @param string|\Magento\Framework\Phrase $carrierTitle
     * @param string|\Magento\Framework\Phrase $methodTitle
     * @param float $price
     * @return \Magento\Quote\Model\Quote\Address\RateResult\Method
     */
    protected function createRateMethod(string $packeteryMethod, string $carrierCode, $carrierTitle, $methodTitle, float $price): \Magento\Quote\Model\Quote\Address\RateResult\Method
    {
        $method = $this->rateMethodFactory->create();
        $method->setCarrier($carrierCode);

        if (empty($method->getCarrierTitle())) {
            $method->setCarrierTitle($carrierTitle);
        }

        $method->setMethod($packeteryMethod);

        if (empty($method->getMethodTitle())) {
            $method->setMethodTitle($methodTitle);
        }

        $method->setCost($price);
        $method->setPrice($price);

        return $method;
    }

    /**
     * @param \Packetery\Checkout\Model\Pricingrule $pricingRule
     * @return array
     */
    protected function getWeightRulesByPricingRule(Pricingrule $pricingRule): array
    {
        $collection = $this->weightRuleCollectionFactory->create();
        $collection->addFilter('packetery_pricing_rule_id', $pricingRule->getId());
        return $collection->getItems();
    }

    /**
     * @param \Packetery\Checkout\Model\Pricingrule|null $pricingrule
     * @param float|null $globalFreeShipping
     * @return float|null
     */
    protected function getFreeShippingThreshold(?Pricingrule $pricingrule, ?float $globalFreeShipping): ?float
    {
        $countryFreeShipping = ($pricingrule ? $pricingrule->getFreeShipment() : null);

        if (is_numeric($countryFreeShipping)) {
            $freeShipping = $countryFreeShipping;
        } elseif (is_numeric($globalFreeShipping)) {
            $freeShipping = $globalFreeShipping;
        } else {
            $freeShipping = null; // disabled
        }

        return ($freeShipping === null ? null : (float)$freeShipping);
    }
}
