<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Packet;

class SubmitPreconditions
{
    /** @var \Packetery\Checkout\Model\ResourceModel\Packet\CollectionFactory */
    private $packetCollectionFactory;

    public function __construct(
        \Packetery\Checkout\Model\ResourceModel\Packet\CollectionFactory $packetCollectionFactory
    ) {
        $this->packetCollectionFactory = $packetCollectionFactory;
    }

    public function hasRequiredConfig(?\Packetery\Checkout\Model\Carrier\Imp\Packetery\Config $config): bool
    {
        return $config !== null && $config->getApiPassword() !== null && $config->getSender() !== null;
    }

    public function isAlreadySubmitted(string $orderNumber): bool
    {
        $collection = $this->packetCollectionFactory->create();
        $collection->addFieldToFilter('order_number', $orderNumber);

        return $collection->getSize() > 0;
    }
}
