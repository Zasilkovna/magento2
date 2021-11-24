<?php

namespace Packetery\Checkout\Observer\Sales;

class AddressPlaceAfter implements \Magento\Framework\Event\ObserverInterface
{

    /** @var \Magento\Sales\Api\OrderRepositoryInterface */
    protected $orderRepository;

    /** @var \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory */
    private $orderCollectionFactory;

    /** @var \Magento\Framework\Message\ManagerInterface */
    private $messageManager;

    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->messageManager = $messageManager;
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
        $address = \Packetery\Checkout\Model\Address::fromShippingAddress($shippingAddress);

        $data = [
            'address_validated' => false,
            'recipient_street' => $address->getStreet(),
            'recipient_house_number' => $address->getHouseNumber(),
            'recipient_city' => $address->getCity(),
            'recipient_zip' => $address->getZip(),
            'recipient_county' => $address->getCounty(),
            'recipient_longitude' => $address->getLongitude(),
            'recipient_latitude' => $address->getLatitude(),
            'recipient_firstname' => $shippingAddress->getData('firstname'),
            'recipient_lastname' => $shippingAddress->getData('lastname'),
            'recipient_company' => $shippingAddress->getData('company'),
            'recipient_email' => $shippingAddress->getData('email'),
            'recipient_phone' => $shippingAddress->getData('telephone'),
        ];

        $this->messageManager->addSuccessMessage(__('Packeta shipping address has been updated'));

        $orderCollection = $this->orderCollectionFactory->create();
        $orderCollection->addFilter('order_number', $orderNumber);
        $orderCollection->setDataToAll($data);
        $orderCollection->save();
    }
}
