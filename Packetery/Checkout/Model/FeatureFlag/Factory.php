<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\FeatureFlag;

use Magento\Framework\Flag;
use Magento\Framework\ObjectManagerInterface;

class Factory
{
    /**
     * Object Manager instance
     *
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Factory constructor
     *
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Create class instance with specified parameters
     *
     * @template T
     * @param class-string<T> $className
     * @param array $data
     * @return \Magento\Framework\Flag
     */
    public function createLoaded(string $className, array $data = []): Flag
    {
        $flag = $this->objectManager->create($className, $data);
        $flag->loadSelf();

        return $flag;
    }
}
