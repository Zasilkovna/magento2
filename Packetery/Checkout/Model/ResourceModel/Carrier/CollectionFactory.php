<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\ResourceModel\Carrier;

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
     * @return Collection
     */
    public function create(array $data = []): Collection
    {
        /** @var Collection $collection */
        $collection = $this->objectManager->create($this->instanceName, $data);

        return $collection;
    }
}
