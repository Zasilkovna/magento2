<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\ResourceModel\Packetdraft;

use Magento\Framework\ObjectManagerInterface;

class CollectionFactory
{
    /**
     * Instance name to create
     *
     * @var string
     */
    protected string $instanceName;

    /**
     * Factory constructor
     *
     * @param ObjectManagerInterface $objectManager
     * @param string $instanceName
     */
    public function __construct(
        protected ObjectManagerInterface $objectManager,
        string $instanceName = Collection::class
    ) {
        $this->instanceName = $instanceName;
    }

    /**
     * Create class instance with specified parameters
     *
     * @param array $data Class constructor arguments to override auto-wiring or specify non-service arguments.
     * @return Collection
     */
    public function create(array $data = []): Collection
    {
        /** @var Collection $collection */
        $collection = $this->objectManager->create($this->instanceName, $data);

        return $collection;
    }

    /**
     * Creates a Collection specifically for inserting new entries into the DB
     *
     * @param array $data Class constructor arguments to override auto-wiring or specify non-service arguments.
     * @return Collection
     */
    public function createForDbInsert(array $data = []): Collection
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
