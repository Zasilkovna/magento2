<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model;

class PacketRepository
{
    /** @var \Packetery\Checkout\Model\ResourceModel\Packet\CollectionFactory */
    private $packetCollectionFactory;

    /** @var \Packetery\Checkout\Model\ResourceModel\Packet */
    private $packetResource;

    public function __construct(
        \Packetery\Checkout\Model\ResourceModel\Packet\CollectionFactory $packetCollectionFactory,
        \Packetery\Checkout\Model\ResourceModel\Packet $packetResource
    ) {
        $this->packetCollectionFactory = $packetCollectionFactory;
        $this->packetResource = $packetResource;
    }

    public function findLatestByOrderNumber(string $orderNumber): ?Packet
    {
        $collection = $this->packetCollectionFactory->create();
        $collection->addFieldToFilter('order_number', $orderNumber);
        $collection->setOrder('id', 'DESC');
        $collection->setPageSize(1);
        $items = $collection->getItems();
        if ($items === []) {
            return null;
        }

        $first = reset($items);
        if (!$first instanceof Packet) {
            return null;
        }

        return $first;
    }

    public function save(Packet $packet): void
    {
        $this->packetResource->save($packet);
    }
}
