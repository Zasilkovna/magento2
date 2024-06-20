<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\ResourceModel\Packetdraft;

class CollectionFactory
{
    /**
     * Object Manager instance
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Instance name to create
     *
     * @var string
     */
    protected $instanceName;

    /**
     * Factory constructor
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param string $instanceName
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager, $instanceName = Collection::class)
    {
        $this->objectManager = $objectManager;
        $this->instanceName = $instanceName;
    }

    /**
     * Create class instance with specified parameters
     *
     * @param array $data Class constructor arguments to override auto-wiring or specify non-service arguments.
     * @return \Packetery\Checkout\Model\ResourceModel\Packetdraft\Collection
     */
    public function create(array $data = [])
    {
        /** @var \Packetery\Checkout\Model\ResourceModel\Packetdraft\Collection $collection */
        $collection = $this->objectManager->create($this->instanceName, $data);

        return $collection;
    }

    /**
     * Creates a Collection specifically for inserting new entries into the DB
     *
     * @param array $data Class constructor arguments to override auto-wiring or specify non-service arguments.
     * @return \Packetery\Checkout\Model\ResourceModel\Packetdraft\Collection
     */
    public function createForDbInsert(array $data = []): \Packetery\Checkout\Model\ResourceModel\Packetdraft\Collection
    {
        $collection = $this->create($data);
        $collection->getSelect()->where('0');

        return $collection;
    }

    /**
     * Saves Packet Draft data to DB
     *
     * @throws \Exception
     * @param array $data
     */
    public function saveData(array $data): void
    {
        $collection = $this->createForDbInsert();
        $packetDraft = $collection->getNewEmptyItem();
        $packetDraft->setData($data);
        $collection->addItem($packetDraft);

        $collection->save();
    }
}
