<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\Order\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

class Actions extends Column
{
    /** @var \Magento\Framework\UrlInterface */
    private $_urlBuilder;

    /** @var string */
    private $_viewUrl;

    /** @var \Magento\Sales\Model\OrderFactory */
    private $orderFactory;

    /** @var \Packetery\Checkout\Model\Carrier\CarrierFactory */
    private $carrierFactory;

    /** @var \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory */
    private $packeteryOrderCollectionFactory;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context
     * @param \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory
     * @param \Magento\Backend\Model\UrlInterface $urlBuilder
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Packetery\Checkout\Model\Carrier\CarrierFactory $carrierFactory
     * @param \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $packeteryOrderCollectionFactory
     * @param string $viewUrl
     * @param array $components
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Packetery\Checkout\Model\Carrier\CarrierFactory $carrierFactory,
        \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $packeteryOrderCollectionFactory,
        $viewUrl = '',
        array $components = [],
        array $data = []
    ) {
        $this->_urlBuilder = $urlBuilder;
        $this->_viewUrl    = $viewUrl;
        $this->orderFactory = $orderFactory;
        $this->carrierFactory = $carrierFactory;
        $this->packeteryOrderCollectionFactory = $packeteryOrderCollectionFactory;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            $carrierCache = [];
            foreach ($dataSource['data']['items'] as &$item) {
                $name = $this->getData('name');

                $orderNumber =  $item['order_number'];
                $order = $this->orderFactory->create()->loadByIncrementId($orderNumber);
                $shippingMethod = (string) $order->getShippingMethod();

                $item[$name]['orderDetail'] = [
                    'href'  => $this->_urlBuilder->getUrl('sales/order/view', ['order_id' => $order->getId()]),
                    'label' => __('Order detail')
                ];

                if ($shippingMethod) {
                    $item[$name]['view'] = [
                        'href'  => $this->_urlBuilder->getUrl($this->_viewUrl, ['id' => $item['id']]),
                        'label' => __('Edit')
                    ];
                    $item[$name]['submit'] = [
                        'href' => $this->_urlBuilder->getUrl('packetery/packet/submit', ['order_id' => $item['id']]),
                        'label' => __('Submit to Packeta'),
                        'confirm' => [
                            'title' => __('Submit to Packeta'),
                            'message' => __('Do you really want to submit this packet to Packeta?'),
                        ],
                    ];
                }

                $packetNumber = isset($item['packet_number']) ? trim((string) $item['packet_number']) : '';
                if ($packetNumber === '' || !$shippingMethod) {
                    continue;
                }

                $packeteryCollection = $this->packeteryOrderCollectionFactory->create();
                $packeteryCollection->addFieldToFilter('id', (int) $item['id']);
                $packeteryOrder = $packeteryCollection->getFirstItem();
                if (!$packeteryOrder->getId()) {
                    continue;
                }

                if (\Packetery\Checkout\Model\Carrier\ShippingRateCode::isPacketery($shippingMethod) === false) {
                    continue;
                }

                $shippingRateCode = \Packetery\Checkout\Model\Carrier\ShippingRateCode::fromString($shippingMethod);
                $carrierCode = $shippingRateCode->getCarrierCode();

                $storeId = (int) $order->getStoreId();
                $carrier = $this->carrierFactory->createCached($carrierCache, $carrierCode, $storeId);
                if (!$carrier instanceof \Magento\Shipping\Model\Carrier\AbstractCarrier) {
                    continue;
                }

                $format = $carrier->getPacketeryConfig()->getLabelFormat();
                $maxOffset = \Packetery\Checkout\Model\Label\LabelFormats::getMaxOffset($format);
                if ($maxOffset === 0) {
                    $printHref = $this->_urlBuilder->getUrl(
                        'packetery/packet/printlabel',
                        ['order_id' => $item['id'], 'offset' => 0]
                    );
                } else {
                    $printHref = $this->_urlBuilder->getUrl(
                        'packetery/packet/printlabelform',
                        ['order_id' => $item['id']]
                    );
                }

                $item[$name]['printLabel'] = [
                    'href' => $printHref,
                    'label' => __('Print label'),
                    'target' => '_blank',
                ];
            }
        }

        return $dataSource;
    }
}
