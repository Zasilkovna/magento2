<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model;

class OrderRepository
{
    /** @var \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory */
    private $orderCollectionFactory;

    public function __construct(
        \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
    }

    public function findById(int $orderId): ?Order
    {
        $collection = $this->orderCollectionFactory->create();
        $collection->addFieldToFilter('id', $orderId);
        $collection->setPageSize(1);
        $items = $collection->getItems();
        if ($items === []) {
            return null;
        }

        $first = reset($items);
        if (!$first instanceof Order) {
            return null;
        }

        return $first;
    }
}
