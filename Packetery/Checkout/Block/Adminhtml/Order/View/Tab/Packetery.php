<?php

declare(strict_types=1);

namespace Packetery\Checkout\Block\Adminhtml\Order\View\Tab;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Packetery\Checkout\Model\AdultContentResolver;
use Packetery\Checkout\Model\Carrier\Methods;
use Packetery\Checkout\Model\Carrier\ShippingRateCode;
use Packetery\Checkout\Model\Dimensions\Converter;
use Packetery\Checkout\Model\OrderCurrencyResolver;
use Packetery\Checkout\Model\Packet\TrackingUrlFactory;
use Packetery\Checkout\Model\PacketRepository;
use Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory;

class Packetery extends Template implements TabInterface
{
    /** @var OrderRepositoryInterface */
    private $orderRepository;

    /** @var CollectionFactory */
    private $orderCollectionFactory;

    /** @var PacketRepository */
    private $packetRepository;

    /** @var TrackingUrlFactory */
    private $trackingUrlFactory;

    /** @var Converter */
    private $dimensionsConverter;

    /** @var OrderCurrencyResolver */
    private $orderCurrencyResolver;

    /** @var \Packetery\Checkout\Model\Order|null */
    private $packeteryOrder = null;

    /** @var bool */
    private $packeteryOrderLoaded = false;

    /** @var \Packetery\Checkout\Model\Packet|null */
    private $packet = null;

    /** @var bool */
    private $packetLoaded = false;

    /** @var OrderInterface|null */
    private $magentoOrder = null;

    /** @var bool */
    private $magentoOrderLoaded = false;

    public function __construct(
        Template\Context $context,
        OrderRepositoryInterface $orderRepository,
        CollectionFactory $orderCollectionFactory,
        PacketRepository $packetRepository,
        TrackingUrlFactory $trackingUrlFactory,
        Converter $dimensionsConverter,
        OrderCurrencyResolver $orderCurrencyResolver,
        array $data = []
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->packetRepository = $packetRepository;
        $this->trackingUrlFactory = $trackingUrlFactory;
        $this->dimensionsConverter = $dimensionsConverter;
        $this->orderCurrencyResolver = $orderCurrencyResolver;

        parent::__construct($context, $data);
    }

    public function getTabLabel()
    {
        return __('Packeta');
    }

    public function getTabTitle()
    {
        return __('Packeta');
    }

    public function canShowTab()
    {
        $order = $this->getOrder();
        if ($order === null) {
            return false;
        }

        $shippingMethod = (string) $order->getShippingMethod();

        return $shippingMethod !== ''
            && ShippingRateCode::isPacketery($shippingMethod)
            && $this->getPacketeryOrderId() !== null;
    }

    public function isHidden()
    {
        return false;
    }

    public function getTabClass()
    {
        return '';
    }

    /**
     * '#' because the tab is inline-rendered, not AJAX-loaded (Magento core #16174)
     */
    public function getTabUrl()
    {
        return '#';
    }

    public function isAjaxLoaded()
    {
        return false;
    }

    /**
     * Non-Packeta order renders nothing, so the form and its widget script never load here
     */
    protected function _toHtml()
    {
        $id = $this->getPacketeryOrderId();
        if ($id === null) {
            return '';
        }

        return $this->renderChildFormForPacketeryOrderId($id);
    }

    /**
     * Points the "id" request param at the packetery_order id while the nested
     * packetery_order_detail_form renders, then restores it so sibling blocks on
     * sales_order_view that read "id" are not affected
     */
    private function renderChildFormForPacketeryOrderId(int $id): string
    {
        $request = $this->getRequest();
        $originalId = $request->getParam('id');
        $request->setParams(['id' => $id]);

        $html = parent::_toHtml();

        $request->setParams(['id' => $originalId]);

        return $html;
    }

    public function getPacketeryOrder(): ?\Packetery\Checkout\Model\Order
    {
        if ($this->packeteryOrderLoaded) {
            return $this->packeteryOrder;
        }

        $this->packeteryOrderLoaded = true;

        $order = $this->getOrder();
        if ($order !== null) {
            $collection = $this->orderCollectionFactory->create();
            $collection->addFieldToFilter('order_number', $order->getIncrementId());
            $item = $collection->getFirstItem();
            if ($item->getId()) {
                $this->packeteryOrder = $item;
            }
        }

        return $this->packeteryOrder;
    }

    public function getPacketeryOrderId(): ?int
    {
        $packeteryOrder = $this->getPacketeryOrder();

        return $packeteryOrder !== null ? (int) $packeteryOrder->getId() : null;
    }

    public function isSubmitted(): bool
    {
        return $this->getPacket() !== null;
    }

    public function getPacket(): ?\Packetery\Checkout\Model\Packet
    {
        if ($this->packetLoaded) {
            return $this->packet;
        }
        $this->packetLoaded = true;

        $packeteryOrder = $this->getPacketeryOrder();
        if ($packeteryOrder !== null) {
            $found = $this->packetRepository->findLatestByOrderNumber($packeteryOrder->getOrderNumber());
            if ($found !== null) {
                $this->packet = $found;
            }
        }

        return $this->packet;
    }

    public function getTrackingUrl(): ?string
    {
        $packet = $this->getPacket();

        return $packet !== null ? $this->trackingUrlFactory->create($packet->getPacketNumber()) : null;
    }

    public function getTrackingNumber(): ?string
    {
        $packet = $this->getPacket();

        return $packet !== null ? $this->trackingUrlFactory->formatNumber($packet->getPacketNumber()) : null;
    }

    /**
     * The same currency fallback the edit form uses (stored Packeta currency, else the Magento order currency)
     */
    public function getCurrency(): string
    {
        return (string) $this->orderCurrencyResolver->resolve($this->getPacketeryOrder(), $this->getOrder());
    }

    public function getDeliveryLabel(): string
    {
        $order = $this->getPacketeryOrder();
        if ($order === null) {
            return '';
        }

        if ($this->isPickupPointDelivery() === true) {
            return (string) $order->getPointName();
        }

        return $this->formatAddress($order);
    }

    public function getDeliveryLabelHeading(): string
    {
        if ($this->isPickupPointDelivery() === true) {
            return (string) __('Pick-up point');
        }

        return (string) __('Delivery address');
    }

    private function getOrder(): ?OrderInterface
    {
        if ($this->magentoOrderLoaded) {
            return $this->magentoOrder;
        }

        $this->magentoOrderLoaded = true;

        $orderId = (int) $this->getRequest()->getParam('order_id');
        if ($orderId > 0) {
            try {
                $this->magentoOrder = $this->orderRepository->get($orderId);
            } catch (NoSuchEntityException) {
                $this->magentoOrder = null;
            }
        }

        return $this->magentoOrder;
    }

    private function resolveMethod(): ?string
    {
        $order = $this->getOrder();
        if ($order === null) {
            return null;
        }

        $shippingMethod = (string) $order->getShippingMethod();
        if (!ShippingRateCode::isPacketery($shippingMethod)) {
            return null;
        }

        return ShippingRateCode::fromString($shippingMethod)
            ->getMethodCode()
            ->getMethod();
    }

    private function isPickupPointDelivery(): bool
    {
        $method = $this->resolveMethod();

        return $method !== null && Methods::isPickupPointDelivery($method);
    }

    /**
     * Age verification is offered only where the edit form offers it: the same eligibility the form uses,
     * so the read-only detail never shows a row the order could not have set
     */
    public function isAdultContentEligible(): bool
    {
        $method = $this->resolveMethod();
        $order = $this->getOrder();

        if ($method === null || $order === null) {
            return false;
        }

        $shippingAddress = $order->getShippingAddress();
        if ($shippingAddress === null) {
            return false;
        }

        $packeteryOrder = $this->getPacketeryOrder();
        if ($packeteryOrder === null) {
            return false;
        }

        return AdultContentResolver::isEligibleForAdultContent($method, (string) $shippingAddress->getCountryId(), $packeteryOrder->isCarrier());
    }

    private function formatAddress(\Packetery\Checkout\Model\Order $order): string
    {
        $address = $order->getRecipientAddress();

        $parts = array_filter([
            $address->getStreet(),
            $address->getHouseNumber(),
            $address->getCity(),
            $address->getZip(),
        ]);

        return implode(', ', $parts);
    }

    /** Null when any snapshot dimension is missing, so label()'s floats can't throw */
    public function getDimensionsLabel(): ?string
    {
        $packet = $this->getPacket();
        if ($packet === null) {
            return null;
        }

        $name = $packet->getBoxName();
        $depth = $packet->getBoxDepth();
        $width = $packet->getBoxWidth();
        $height = $packet->getBoxHeight();

        if ($name === null || $depth === null || $width === null || $height === null) {
            return null;
        }

        return $this->dimensionsConverter->label($name, $depth, $width, $height);
    }
}
