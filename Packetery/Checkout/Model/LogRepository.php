<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model;

class LogRepository
{
    public function __construct(
        private readonly \Packetery\Checkout\Model\LogFactory $logFactory,
        private readonly \Packetery\Checkout\Model\ResourceModel\Log $logResource
    ) {
    }

    /**
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(\Packetery\Checkout\Model\Log $log): \Packetery\Checkout\Model\Log
    {
        try {
            $this->logResource->save($log);
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\CouldNotSaveException(__('Could not save log: %1', $e->getMessage()), $e);
        }

        return $log;
    }

    public function findById(int $id): ?\Packetery\Checkout\Model\Log
    {
        $log = $this->logFactory->create();
        $this->logResource->load($log, $id);
        if ($log->getId() === null) {
            return null;
        }

        return $log;
    }

    public function deleteOlderThan(string $thresholdUtc): int
    {
        return $this->logResource->deleteOlderThan($thresholdUtc);
    }
}
