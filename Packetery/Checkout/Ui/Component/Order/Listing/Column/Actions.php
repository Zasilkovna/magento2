<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\Order\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use Packetery\Checkout\Model\Carrier\Methods;

class Actions extends Column
{
    /** @var UrlInterface */
    private $_urlBuilder;

    /** @var string */
    private $_viewUrl;

    /** @var \Magento\Sales\Model\OrderFactory */
    private $orderFactory;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context
     * @param \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory
     * @param \Magento\Framework\Url $urlBuilder
     * @param string $viewUrl
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        $viewUrl = '',
        array $components = [],
        array $data = []
    ) {
        $this->_urlBuilder = $urlBuilder;
        $this->_viewUrl    = $viewUrl;
        $this->orderFactory = $orderFactory;
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
            foreach ($dataSource['data']['items'] as &$item) {
                $name = $this->getData('name');

                $orderNumber =  $item['order_number'];
                $order = $this->orderFactory->create()->loadByIncrementId($orderNumber);
                $shippingMethod = $order->getShippingMethod(true);
                $packetNumber = isset($item['packet_number']) ? trim((string) $item['packet_number']) : '';

                $item[$name]['orderDetail'] = [
                    'href'  => $this->_urlBuilder->getUrl('sales/order/view', ['order_id' => $order->getId()]),
                    'label' => __('Order detail')
                ];

                if ($shippingMethod) {
                    $item[$name]['view'] = [
                        'href'  => $this->_urlBuilder->getUrl($this->_viewUrl, ['id' => $item['id']]),
                        'label' => __('Edit')
                    ];
                    if ($packetNumber === '') {
                        $item[$name]['submit'] = [
                            'href' => $this->_urlBuilder->getUrl('packetery/packet/submit', ['order_id' => $item['id']]),
                            'label' => __('Submit to Packeta'),
                            'confirm' => [
                                'title' => __('Submit to Packeta'),
                                'message' => __('Do you really want to submit this packet to Packeta?'),
                            ],
                        ];
                    }

                    if ($packetNumber !== '') {
                        $trackingNumber = 'Z' . $packetNumber;
                        $item[$name]['cancel'] = [
                            'href' => $this->_urlBuilder->getUrl('packetery/packet/cancel', [
                                'order_id' => $item['id'],
                                'packet_number' => $packetNumber,
                            ]),
                            'label' => __('Cancel packet'),
                            'confirm' => [
                                'title' => __('Cancel packet'),
                                'message' => __('Do you really want to cancel the packet %1 for order %2 ?', $trackingNumber, $orderNumber),
                            ],
                        ];
                    }
                }
            }
        }

        return $dataSource;
    }
}
