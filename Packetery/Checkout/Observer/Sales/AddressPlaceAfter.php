<?php

namespace Packetery\Checkout\Observer\Sales;

class AddressPlaceAfter implements \Magento\Framework\Event\ObserverInterface
{

    /** @var \Magento\Sales\Api\OrderRepositoryInterface */
    protected $orderRepository;

    /** @var \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory */
    private $orderCollectionFactory;

    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderCollectionFactory = $orderCollectionFactory;
    }

    /**
     * Observer is triggered if order address is changed (from admin).
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return void
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    )
    {
        $orderId = $observer->getData('order_id');
        $order = $this->orderRepository->get($orderId);
        $orderNumber = $order->getIncrementId();
        $shippingAddress = $order->getShippingAddress();

        // TODO: save shipping address or enforce widget?
        $data = [
            'recipient_firstname' => $shippingAddress->getData('firstname'),
            'recipient_lastname' => $shippingAddress->getData('lastname'),
            'recipient_company' => $shippingAddress->getData('company'),
            'recipient_email' => $shippingAddress->getData('email'),
            'recipient_phone' => $shippingAddress->getData('telephone'),
        ];

        $orderCollection = $this->orderCollectionFactory->create();
        $orderCollection->addFilter('order_number', $orderNumber);
        $orderCollection->setDataToAll($data);
        $orderCollection->save();
    }
}
