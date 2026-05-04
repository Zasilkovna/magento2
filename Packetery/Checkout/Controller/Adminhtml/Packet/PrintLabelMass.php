<?php

declare(strict_types=1);

namespace Packetery\Checkout\Controller\Adminhtml\Packet;

class PrintLabelMass extends \Magento\Backend\App\Action
{
    public const ADMIN_RESOURCE = 'Packetery_Checkout::packetery';
    private const LABEL_TYPE_PACKETA = 'packeta';
    private const LABEL_TYPE_CARRIER = 'carrier';

    /** @var \Magento\Framework\Controller\Result\RawFactory */
    private $resultRawFactory;

    /** @var \Magento\Ui\Component\MassAction\Filter */
    private $massActionFilter;

    /** @var \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory */
    private $packeteryOrderCollectionFactory;

    /** @var \Magento\Sales\Model\OrderFactory */
    private $magentoOrderFactory;

    /** @var \Packetery\Checkout\Model\PacketRepository */
    private $packetRepository;

    /** @var \Packetery\Checkout\Model\Carrier\CarrierFactory */
    private $carrierFactory;

    /** @var \Packetery\Checkout\Model\Api\SoapApiClient */
    private $soapApiClient;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Ui\Component\MassAction\Filter $massActionFilter,
        \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $packeteryOrderCollectionFactory,
        \Magento\Sales\Model\OrderFactory $magentoOrderFactory,
        \Packetery\Checkout\Model\PacketRepository $packetRepository,
        \Packetery\Checkout\Model\Carrier\CarrierFactory $carrierFactory,
        \Packetery\Checkout\Model\Api\SoapApiClient $soapApiClient
    ) {
        parent::__construct($context);
        $this->resultRawFactory = $resultRawFactory;
        $this->massActionFilter = $massActionFilter;
        $this->packeteryOrderCollectionFactory = $packeteryOrderCollectionFactory;
        $this->magentoOrderFactory = $magentoOrderFactory;
        $this->packetRepository = $packetRepository;
        $this->carrierFactory = $carrierFactory;
        $this->soapApiClient = $soapApiClient;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Raw|\Magento\Framework\Controller\Result\Redirect
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create()->setPath('packetery/order/index');
        $labelType = (string) $this->getRequest()->getParam('label_type');
        if ($labelType !== self::LABEL_TYPE_PACKETA && $labelType !== self::LABEL_TYPE_CARRIER) {
            $this->messageManager->addErrorMessage(__('No eligible shipments found for selected action.'));
            return $resultRedirect;
        }

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

        $groups = [];

        foreach ($packeteryOrders as $packeteryOrder) {
            $magentoOrder = $this->magentoOrderFactory->create()->loadByIncrementId($packeteryOrder->getOrderNumber());
            if (!$magentoOrder->getId()) {
                continue;
            }

            $shippingMethod = (string) $magentoOrder->getShippingMethod();
            if (\Packetery\Checkout\Model\Carrier\ShippingRateCode::isPacketery($shippingMethod) === false) {
                continue;
            }

            $shippingRateCode = \Packetery\Checkout\Model\Carrier\ShippingRateCode::fromString($shippingMethod);
            $carrierCode = $shippingRateCode->getCarrierCode();
            $isCarrierDelivery = $carrierCode === \Packetery\Checkout\Model\Carrier\Imp\PacketeryPacketaDynamic\Brain::getCarrierCodeStatic();
            if ($labelType === self::LABEL_TYPE_CARRIER && $isCarrierDelivery === false) {
                continue;
            }
            if ($labelType === self::LABEL_TYPE_PACKETA && $isCarrierDelivery) {
                continue;
            }

            $storeId = (int) $magentoOrder->getStoreId();
            $carrier = $this->carrierFactory->create($carrierCode, $storeId);
            if (!$carrier instanceof \Magento\Shipping\Model\Carrier\AbstractCarrier) {
                continue;
            }

            $packeteryCarrierCode = \Packetery\Checkout\Model\Carrier\Imp\Packetery\Brain::getCarrierCodeStatic();
            $packeteryCarrier = $this->carrierFactory->create($packeteryCarrierCode, $storeId);
            if (!$packeteryCarrier instanceof \Magento\Shipping\Model\Carrier\AbstractCarrier) {
                continue;
            }

            $apiPassword = (string) ($packeteryCarrier->getPacketeryConfig()->getApiPassword() ?? '');
            if ($apiPassword === '') {
                continue;
            }

            $currentFormat = $carrier->getPacketeryConfig()->getLabelFormat();
            if (!isset($groups[$apiPassword])) {
                $groups[$apiPassword] = [
                    'label_format' => $currentFormat,
                    'packet_ids' => [],
                    'carrier_pairs' => [],
                    'packets' => [],
                ];
            } elseif ($groups[$apiPassword]['label_format'] !== $currentFormat) {
                $this->messageManager->addErrorMessage(
                    __('Selected orders use different label formats for the same Packeta API account. Print labels separately by adjusting store configuration.')
                );
                return $resultRedirect;
            }

            $packet = $this->packetRepository->findLatestByOrderNumber((string) $packeteryOrder->getOrderNumber());
            if ($packet === null) {
                continue;
            }

            $packetId = $packet->getPacketNumber();
            if ($packetId === '') {
                continue;
            }

            if ($labelType === self::LABEL_TYPE_CARRIER) {
                $courierNumber = $packet->getCourierNumber();
                if ($courierNumber === null || $courierNumber === '') {
                    $courierNumberResult = $this->soapApiClient->packetCourierNumber(
                        new \Packetery\Checkout\Model\Api\Request\PacketCourierNumberRequest($apiPassword, $packetId)
                    );
                    $courierNumber = $courierNumberResult->getCourierNumber();
                    if ($courierNumber === null || $courierNumber === '') {
                        continue;
                    }
                    $packet->setCourierNumber($courierNumber);
                    $this->packetRepository->save($packet);
                }

                $groups[$apiPassword]['carrier_pairs'][] = [
                    'packetId' => $packetId,
                    'courierNumber' => $courierNumber,
                ];
            }

            $groups[$apiPassword]['packet_ids'][] = $packetId;
            $groups[$apiPassword]['packets'][] = $packet;
        }

        if ($groups === []) {
            $this->messageManager->addErrorMessage(__('No eligible shipments found for selected action.'));
            return $resultRedirect;
        }

        $groups = array_filter(
            $groups,
            static function (array $group) use ($labelType): bool {
                if ($labelType === self::LABEL_TYPE_CARRIER) {
                    return $group['carrier_pairs'] !== [];
                }

                return $group['packet_ids'] !== [];
            }
        );

        if ($groups === []) {
            $this->messageManager->addErrorMessage(__('No eligible shipments found for selected action.'));
            return $resultRedirect;
        }

        $offset = 0;
        $pdfContentsList = [];

        foreach ($groups as $apiPassword => $group) {
            if ($labelType === self::LABEL_TYPE_CARRIER) {
                $labelsResult = $this->soapApiClient->packetsCourierLabelsPdf(
                    new \Packetery\Checkout\Model\Api\Request\PacketsCourierLabelsPdfRequest(
                        (string) $apiPassword,
                        $group['carrier_pairs'],
                        $offset,
                        (string) $group['label_format']
                    )
                );
            } else {
                $labelsResult = $this->soapApiClient->packetsLabelsPdf(
                    new \Packetery\Checkout\Model\Api\Request\PacketsLabelsPdfRequest(
                        (string) $apiPassword,
                        $group['packet_ids'],
                        (string) $group['label_format'],
                        $offset
                    )
                );
            }

            $contents = $labelsResult->getPdfContents();
            if ($contents === null) {
                $fault = (string) $labelsResult->getFaultString();
                $this->messageManager->addErrorMessage(
                    new \Packetery\Checkout\Model\Misc\ComboPhrase(
                        [
                            __('The label could not be generated.'),
                            ' ',
                            $fault,
                        ]
                    )
                );
                continue;
            }

            $pdfContentsList[] = $contents;
        }

        if ($pdfContentsList === []) {
            $this->messageManager->addErrorMessage(__('No eligible shipments found for selected action.'));
            return $resultRedirect;
        }

        $packetsToPersist = [];
        foreach ($groups as $group) {
            foreach ($group['packets'] as $packet) {
                $packetsToPersist[] = $packet;
            }
        }

        foreach ($packetsToPersist as $packet) {
            $packet->setLabelPrintedAt(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
            $this->packetRepository->save($packet);
        }

        $payload = [
            'pdfs' => array_map(
                static function (string $binary): string {
                    return base64_encode($binary);
                },
                $pdfContentsList
            ),
        ];

        $request = $this->getRequest();
        $isAjax = $request instanceof \Magento\Framework\App\Request\Http && $request->isXmlHttpRequest();

        if ($isAjax) {
            $raw = $this->resultRawFactory->create();
            $raw->setHeader('Content-Type', 'application/json; charset=UTF-8', true);
            $raw->setContents((string) json_encode($payload, JSON_THROW_ON_ERROR));

            return $raw;
        }

        $raw = $this->resultRawFactory->create();
        $raw->setHeader('Content-Type', 'text/html; charset=UTF-8', true);
        $raw->setContents($this->renderMassPrintHtmlResponse($payload));

        return $raw;
    }

    private function renderMassPrintHtmlResponse(array $payload): string
    {
        $payloadJson = json_encode(
            $payload,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
        );
        $listingUrlJson = json_encode(
            $this->getUrl('packetery/order/index'),
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
        );

        $block = $this->_view
            ->getLayout()
            ->createBlock(\Magento\Framework\View\Element\Template::class);
        $block->setTemplate('Packetery_Checkout::packet/mass_print_result.phtml');
        $block->setData('payload_json', $payloadJson);
        $block->setData('listing_url_json', $listingUrlJson);

        return (string) $block->toHtml();
    }

}
