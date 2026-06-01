<?php

declare(strict_types=1);

namespace Packetery\Checkout\Controller\Adminhtml\Packet;

class PrintOrderCollectionMass extends \Magento\Backend\App\Action
{
    public const ADMIN_RESOURCE = 'Packetery_Checkout::packetery';

    private \Magento\Framework\Controller\Result\RawFactory $resultRawFactory;

    private \Magento\Ui\Component\MassAction\Filter $massActionFilter;

    private \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $packeteryOrderCollectionFactory;

    private \Magento\Sales\Model\OrderFactory $magentoOrderFactory;

    private \Packetery\Checkout\Model\PacketRepository $packetRepository;

    private \Packetery\Checkout\Model\Carrier\CarrierFactory $carrierFactory;

    private \Packetery\Checkout\Model\Api\SoapApiClient $soapApiClient;

    private \Packetery\Checkout\Model\OrderCollection\SenderAddressProvider $senderAddressProvider;

    private \Packetery\Checkout\Model\OrderCollection\OrderCollectionRowBuilder $rowBuilder;

    private \Magento\Store\Model\StoreManagerInterface $storeManager;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Ui\Component\MassAction\Filter $massActionFilter,
        \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $packeteryOrderCollectionFactory,
        \Magento\Sales\Model\OrderFactory $magentoOrderFactory,
        \Packetery\Checkout\Model\PacketRepository $packetRepository,
        \Packetery\Checkout\Model\Carrier\CarrierFactory $carrierFactory,
        \Packetery\Checkout\Model\Api\SoapApiClient $soapApiClient,
        \Packetery\Checkout\Model\OrderCollection\SenderAddressProvider $senderAddressProvider,
        \Packetery\Checkout\Model\OrderCollection\OrderCollectionRowBuilder $rowBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->resultRawFactory = $resultRawFactory;
        $this->massActionFilter = $massActionFilter;
        $this->packeteryOrderCollectionFactory = $packeteryOrderCollectionFactory;
        $this->magentoOrderFactory = $magentoOrderFactory;
        $this->packetRepository = $packetRepository;
        $this->carrierFactory = $carrierFactory;
        $this->soapApiClient = $soapApiClient;
        $this->senderAddressProvider = $senderAddressProvider;
        $this->rowBuilder = $rowBuilder;
        $this->storeManager = $storeManager;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Raw|\Magento\Framework\Controller\Result\Redirect
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create()->setPath('packetery/order/index');

        try {
            $collection = $this->massActionFilter->getCollection($this->packeteryOrderCollectionFactory->create());
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $resultRedirect;
        }

        $packeteryOrders = $collection->getItems();
        if ($packeteryOrders === []) {
            $this->messageManager->addErrorMessage(__('No eligible shipments found for selected action.'));
            return $resultRedirect;
        }

        $groups = $this->groupByStore($packeteryOrders);
        if ($groups === []) {
            $this->messageManager->addErrorMessage(__('No eligible shipments found for selected action.'));
            return $resultRedirect;
        }

        $sections = [];
        foreach ($groups as $storeId => $group) {
            $shipmentResult = $this->soapApiClient->createShipment(
                new \Packetery\Checkout\Model\Api\Request\CreateShipmentRequest(
                    $group['api_password'],
                    $group['packet_ids']
                )
            );
            $barcode = $shipmentResult->getBarcode();
            if ($barcode === null || $barcode === '') {
                $this->messageManager->addErrorMessage(__('The packet list could not be generated.'));
                return $resultRedirect;
            }

            $barcodeResult = $this->soapApiClient->barcodePng(
                new \Packetery\Checkout\Model\Api\Request\BarcodePngRequest(
                    $group['api_password'],
                    $barcode
                )
            );
            $pngContents = $barcodeResult->getPngContents();
            if ($pngContents === null || $pngContents === '') {
                $this->messageManager->addErrorMessage(__('The packet list could not be generated.'));
                return $resultRedirect;
            }

            $sender = $this->senderAddressProvider->forStore($storeId);
            $sections[] = [
                'store_name' => (string) $this->storeManager->getStore($storeId)->getName(),
                'sender' => $sender,
                'receiver' => \Packetery\Checkout\Model\OrderCollection\ReceiverAddress::forCountry($sender->getCountryCode()),
                'rows' => $group['rows'],
                'barcode_text' => $shipmentResult->getBarcodeText() ?? $barcode,
                'barcode_png_base64' => base64_encode($pngContents),
                'show_consign_password' => $group['show_consign_password'],
            ];
        }

        $raw = $this->resultRawFactory->create();
        $raw->setHeader('Content-Type', 'text/html; charset=UTF-8', true);
        $raw->setContents($this->renderDispatcherHtml($this->renderCombinedHtml($sections)));

        return $raw;
    }

    /**
     * @param \Packetery\Checkout\Model\Order[] $packeteryOrders
     * @return array<int, array{
     *     api_password: string,
     *     packet_ids: string[],
     *     rows: \Packetery\Checkout\Model\OrderCollection\OrderCollectionRow[],
     *     show_consign_password: bool
     * }>
     */
    private function groupByStore(array $packeteryOrders): array
    {
        $groups = [];

        foreach ($packeteryOrders as $packeteryOrder) {
            $magentoOrder = $this->magentoOrderFactory->create()->loadByIncrementId($packeteryOrder->getOrderNumber());
            if (!$magentoOrder->getId()) {
                continue;
            }

            $packet = $this->packetRepository->findLatestByOrderNumber((string) $packeteryOrder->getOrderNumber());
            if ($packet === null) {
                continue;
            }

            $packetNumber = $packet->getPacketNumber();
            if ($packetNumber === '') {
                continue;
            }

            $storeId = (int) $magentoOrder->getStoreId();
            $packeteryCarrierCode = \Packetery\Checkout\Model\Carrier\Imp\Packetery\Brain::getCarrierCodeStatic();
            $packeteryCarrier = $this->carrierFactory->create($packeteryCarrierCode, $storeId);
            if (!$packeteryCarrier instanceof \Magento\Shipping\Model\Carrier\AbstractCarrier) {
                continue;
            }

            $apiPassword = (string) ($packeteryCarrier->getPacketeryConfig()->getApiPassword() ?? '');
            if ($apiPassword === '') {
                continue;
            }

            if (!isset($groups[$storeId])) {
                $groups[$storeId] = [
                    'api_password' => $apiPassword,
                    'packet_ids' => [],
                    'rows' => [],
                    'show_consign_password' => $packeteryCarrier->getPacketeryConfig()->isShowConsignPassword(),
                ];
            }

            $groups[$storeId]['packet_ids'][] = $packetNumber;
            $groups[$storeId]['rows'][] = $this->rowBuilder->build($packeteryOrder, $packet, $magentoOrder);
        }

        return $groups;
    }

    /**
     * @param array<int, array{
     *     store_name: string,
     *     sender: \Packetery\Checkout\Model\OrderCollection\SenderAddress,
     *     receiver: \Packetery\Checkout\Model\OrderCollection\ReceiverAddress,
     *     rows: \Packetery\Checkout\Model\OrderCollection\OrderCollectionRow[],
     *     barcode_text: string,
     *     barcode_png_base64: string,
     *     show_consign_password: bool
     * }> $sections
     */
    private function renderCombinedHtml(array $sections): string
    {
        $block = $this->_view
            ->getLayout()
            ->createBlock(\Magento\Framework\View\Element\Template::class);
        $block->setTemplate('Packetery_Checkout::order_collection_print.phtml');
        $block->setData('sections', $sections);

        return (string) $block->toHtml();
    }

    private function renderDispatcherHtml(string $combinedHtml): string
    {
        $payloadJson = json_encode(
            ['html_b64' => base64_encode($combinedHtml)],
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
        );
        $listingUrlJson = json_encode(
            $this->getUrl('packetery/order/index'),
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
        );

        $block = $this->_view
            ->getLayout()
            ->createBlock(\Magento\Framework\View\Element\Template::class);
        $block->setTemplate('Packetery_Checkout::order_collection_print_dispatcher.phtml');
        $block->setData('payload_json', $payloadJson);
        $block->setData('listing_url_json', $listingUrlJson);

        return (string) $block->toHtml();
    }
}
