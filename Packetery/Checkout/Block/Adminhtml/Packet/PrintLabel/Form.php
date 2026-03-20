<?php

declare(strict_types=1);

namespace Packetery\Checkout\Block\Adminhtml\Packet\PrintLabel;
class Form extends \Magento\Backend\Block\Template
{
    /** @var \Magento\Framework\Data\Form\FormKey */
    protected $formKey;

    /** @var \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory */
    private $packeteryOrderCollectionFactory;

    /** @var \Packetery\Checkout\Model\ResourceModel\Packet\CollectionFactory */
    private $packetCollectionFactory;

    /** @var \Magento\Sales\Model\OrderFactory */
    private $magentoOrderFactory;

    /** @var \Magento\Shipping\Model\CarrierFactory */
    private $carrierFactory;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $packeteryOrderCollectionFactory,
        \Packetery\Checkout\Model\ResourceModel\Packet\CollectionFactory $packetCollectionFactory,
        \Magento\Sales\Model\OrderFactory $magentoOrderFactory,
        \Magento\Shipping\Model\CarrierFactory $carrierFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->formKey = $formKey;
        $this->packeteryOrderCollectionFactory = $packeteryOrderCollectionFactory;
        $this->packetCollectionFactory = $packetCollectionFactory;
        $this->magentoOrderFactory = $magentoOrderFactory;
        $this->carrierFactory = $carrierFactory;
    }

    public function getFormKeyValue(): string
    {
        return $this->formKey->getFormKey();
    }

    public function getOrderId(): int
    {
        return (int) $this->getRequest()->getParam('order_id');
    }

    public function getPrintTargetUrl(): string
    {
        return $this->getUrl(
            'packetery/packet/printlabel',
            ['order_id' => $this->getOrderId()]
        );
    }

    public function getBackUrl(): string
    {
        return $this->getUrl('packetery/order/index');
    }

    public function getPageHeading(): \Magento\Framework\Phrase
    {
        $packetNumber = $this->resolvePacketNumber();
        if ($packetNumber === '') {
            return __('Print label');
        }

        return __('Label print of packet %1', 'Z' . $packetNumber);
    }

    /**
     * @return array<int, \Magento\Framework\Phrase>
     */
    public function getOffsetOptionLabels(): array
    {
        $packeteryOrder = $this->loadPacketeryOrder();
        if ($packeteryOrder === null) {
            return [];
        }

        $magentoOrder = $this->magentoOrderFactory->create()->loadByIncrementId($packeteryOrder->getOrderNumber());
        if (!$magentoOrder->getId()) {
            return [];
        }

        $storeId = (int) $magentoOrder->getStoreId();
        $shippingMethod = (string) $magentoOrder->getShippingMethod();
        if (\Packetery\Checkout\Model\Carrier\ShippingRateCode::isPacketery($shippingMethod) === false) {
            return [];
        }

        $shippingRateCode = \Packetery\Checkout\Model\Carrier\ShippingRateCode::fromString($shippingMethod);
        $carrierCode = $shippingRateCode->getCarrierCode();
        $carrier = $this->carrierFactory->create($carrierCode, $storeId);
        if (!$carrier instanceof \Magento\Shipping\Model\Carrier\AbstractCarrier) {
            return [];
        }

        $format = $carrier->getPacketeryConfig()->getLabelFormat();
        $max = \Packetery\Checkout\Model\Label\LabelFormats::getMaxOffset($format);
        $labels = [];
        for ($i = 0; $i <= $max; $i++) {
            if ($i === 0) {
                $labels[$i] = __("Don't skip any field on a print sheet");
                continue;
            }
            $labels[$i] = __('Skip %1 fields on first sheet', $i);
        }

        return $labels;
    }

    private function resolvePacketNumber(): string
    {
        $packeteryOrder = $this->loadPacketeryOrder();
        if ($packeteryOrder === null) {
            return '';
        }

        $collection = $this->packetCollectionFactory->create();
        $collection->addFieldToFilter('order_number', $packeteryOrder->getOrderNumber());
        $collection->setOrder('id', 'DESC');
        $collection->setPageSize(1);
        $items = $collection->getItems();
        if ($items === []) {
            return '';
        }

        $packet = reset($items);
        if (!$packet instanceof \Packetery\Checkout\Model\Packet) {
            return '';
        }

        return $packet->getPacketNumber();
    }

    private function loadPacketeryOrder(): ?\Packetery\Checkout\Model\Order
    {
        $orderId = $this->getOrderId();
        if ($orderId <= 0) {
            return null;
        }

        $collection = $this->packeteryOrderCollectionFactory->create();
        $collection->addFieldToFilter('id', $orderId);
        $order = $collection->getFirstItem();
        if (!$order->getId()) {
            return null;
        }

        return $order;
    }
}
