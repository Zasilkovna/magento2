<?php

namespace Packetery\Checkout\Controller\Config;

use Magento\Framework\App\Action\HttpGetActionInterface;

class Storeconfig implements HttpGetActionInterface
{
    /** @var \Magento\Framework\Controller\Result\JsonFactory */
    protected $resultJsonFactory;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;

    /** @var string */
    protected $version;

    /** @var \Packetery\Checkout\Helper\Data */
    private $helperData;

    /** @var \Packetery\Checkout\Model\Carrier\Imp\Packetery\Config */
    private $packeteryConfig;

    /** @var \Magento\Framework\Message\ManagerInterface */
    protected $messageManager;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Packetery\Checkout\Helper\Data $helperData,
        \Packetery\Checkout\Model\Carrier\Imp\Packetery\Carrier $packetery,
        private readonly \Packetery\Checkout\Model\Weight\Converter $weightConverter,
        private readonly \Packetery\Checkout\Model\Weight\Resolver $weightResolver,
    ) {
        $this->messageManager = $context->getMessageManager();
        $this->resultJsonFactory = $resultJsonFactory;
        $this->storeManager = $storeManager;
        $this->packeteryConfig = $packetery->getPacketeryConfig();
        $this->helperData = $helperData;
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $store = $this->storeManager->getStore();
            $config = [
                'apiKey' => $this->packeteryConfig->getApiKey(),
                'packetaOptions' => [
                    'webUrl' => $store->getBaseUrl(),
                    'appIdentity' => $this->helperData->getPacketeryAppIdentity(),
                    'language' => $this->helperData->getShortLocale(),
                    'weight' => $this->getFormattedWeight($store),
                ],
                'currentStoreCurrencyCode' => $store->getCurrentCurrencyCode(),
            ];

            $response = [
                'success' => true,
                'value' => json_encode($config),
            ];
        } catch (\Exception $e) {
            $response = [
                'success' => false,
                'value' => __('There was an error during request.'),
            ];

            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $this->resultJsonFactory->create()->setData($response);
    }

    private function getFormattedWeight(\Magento\Store\Api\Data\StoreInterface $store): ?float
    {
        $weight = $this->weightResolver->resolve();
        if ($weight === null || $weight < 0.0) {
            return null;
        }

        return $this->weightConverter->convertToKg($weight, (int)$store->getId());
    }
}
