<?php

declare(strict_types=1);

namespace Packetery\Checkout\Controller\Config;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;

class ShippingRateConfig implements HttpGetActionInterface
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

    public function execute() {
        $carrierCode = $this->request->getParam('carrierCode');
        $methodCode = \Packetery\Checkout\Model\Carrier\MethodCode::fromString($this->request->getParam('methodCode'));
        $countryId = $this->request->getParam('countryId');

        /** @var \Packetery\Checkout\Model\Carrier\AbstractCarrier $carrier */
        $carrier = $this->carrierFactory->create($carrierCode);
        $directionId = $carrier->getPacketeryBrain()->resolvePointId(
            $methodCode->getMethod(),
            $countryId,
            $carrier->getPacketeryBrain()->getDynamicCarrierById($methodCode->getDynamicCarrierId())
        );

        $relatedPricingRule = $this->pricingService->resolvePricingRule(
            $methodCode->getMethod(),
            $countryId,
            $carrierCode,
            $methodCode->getDynamicCarrierId()
        );

        $config = [];
        $config['directionId'] = $directionId;
        $config['addressValidation'] = $relatedPricingRule->getAddressValidation();
        $response = [
            'success' => true,
            'value' => json_encode($config),
        ];

        // todo error catching?

        return $this->resultJsonFactory->create()->setData($response);
    }
}
