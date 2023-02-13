<?php

declare(strict_types=1);

namespace Packetery\Checkout\Controller\Config;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Packetery\Checkout\Model\AddressValidationSelect;
use Packetery\Checkout\Model\Carrier\AbstractCarrier;
use Packetery\Checkout\Model\Carrier\AbstractDynamicCarrier;
use Packetery\Checkout\Model\Carrier\MethodCode;
use Packetery\Checkout\Model\Pricingrule;

class ShippingRatesConfig implements HttpPostActionInterface
{
    /** @var \Magento\Framework\Controller\Result\JsonFactory */
    protected $resultJsonFactory;

    /** @var RequestInterface */
    private $request;

    /** @var \Magento\Shipping\Model\CarrierFactory */
    private $carrierFactory;

    /** @var \Packetery\Checkout\Model\Pricing\Service */
    private $pricingService;

    /**
     * ShippingRateConfig constructor.
     *
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Shipping\Model\CarrierFactory $carrierFactory
     * @param \Packetery\Checkout\Model\Pricing\Service $pricingService
     */
    public function __construct(\Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory, \Magento\Framework\App\RequestInterface $request, \Magento\Shipping\Model\CarrierFactory $carrierFactory, \Packetery\Checkout\Model\Pricing\Service $pricingService) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->request = $request;
        $this->carrierFactory = $carrierFactory;
        $this->pricingService = $pricingService;
    }

    /**
     * @param string $countryId
     * @param string $carrierCode
     * @param string $methodCode
     * @return array
     */
    private function getRateConfig(string $countryId, string $carrierCode, string $methodCode): array {
        $config = [
            'isPacketaRate' => false
        ];

        /** @var \Packetery\Checkout\Model\Carrier\AbstractCarrier $carrier */
        $carrier = $this->carrierFactory->create($carrierCode);

        if (!$carrier instanceof AbstractCarrier) {
            return $config; // rate is not from Packeta
        }

        $methodCodeObject = MethodCode::fromString($methodCode);
        $directionId = $carrier->getPacketeryBrain()->resolvePointId(
            $methodCodeObject->getMethod(),
            $countryId,
            $carrier->getPacketeryBrain()->getDynamicCarrierById($methodCodeObject->getDynamicCarrierId())
        );

        $relatedPricingRule = $this->pricingService->resolvePricingRule(
            $methodCodeObject->getMethod(),
            $countryId,
            $carrierCode,
            $methodCodeObject->getDynamicCarrierId()
        );

        $config['isPacketaRate'] = true;
        $config['directionId'] = $directionId; // for Packeta PP it returns null because it is provided by widget
        $config['addressValidation'] = $relatedPricingRule ? $relatedPricingRule->getAddressValidation() : AddressValidationSelect::NONE;
        $config['isAnyAddressDelivery'] = \Packetery\Checkout\Model\Carrier\Methods::isAnyAddressDelivery($methodCodeObject->getMethod());
        $config['isPickupPointDelivery'] = \Packetery\Checkout\Model\Carrier\Methods::isPickupPointDelivery($methodCodeObject->getMethod());

        $dynamicCarrier = $carrier->getPacketeryBrain()->getDynamicCarrierById($methodCodeObject->getDynamicCarrierId());
        $config['widgetVendors'] = self::buildWidgetVendors([$dynamicCarrier], $relatedPricingRule);

        return $config;
    }

    /**
     * @param AbstractDynamicCarrier[] $dynamicCarriers
     * @return array<int, array>
     */
    public static function buildWidgetVendors(array $dynamicCarriers, ?Pricingrule $relatedPricingRule): array {
        $widgetVendors = [];

        if ($relatedPricingRule !== null) {
            $vendorCodes = $relatedPricingRule->getVendorCodes() ?? [];

            foreach ($vendorCodes as $vendorCode) {
                $widgetVendors[] = [
                    'code' => $vendorCode,
                    'selected' => true,
                ];
            }
        }

        foreach ($dynamicCarriers as $dynamicCarrier) {
            if ($dynamicCarrier instanceof \Packetery\Checkout\Model\Carrier\Imp\Packetery\VendorCarrier) {
                $widgetVendors[] = [
                    'code' => $dynamicCarrier->getVendorCode(),
                    'selected' => true,
                ];
            }
        }

        return $widgetVendors;
    }

    public function execute() {
        $config = [];
        $ratesConfig = [];
        $postData = json_decode($this->request->getContent(), true);
        $shippingRates = $postData['rates'];

        foreach ($shippingRates as $shippingRate) {
            $ratesConfig[$shippingRate['rateCode']] = $this->getRateConfig($shippingRate['countryId'], $shippingRate['carrierCode'], $shippingRate['methodCode']);
        }

        $config['rates'] = $ratesConfig;
        $response = [
            'success' => true,
            'value' => $config,
        ];

        return $this->resultJsonFactory->create()->setData($response);
    }
}
