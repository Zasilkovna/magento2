<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\ResourceModel;

class Log extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct(): void
    {
        $this->_init(\Packetery\Checkout\Model\Log::TABLE_NAME, \Packetery\Checkout\Model\Log::ID);
    }

    public function deleteOlderThan(string $thresholdUtc): int
    {
        $connection = $this->getConnection();

        return (int) $connection->delete(
            $this->getMainTable(),
            [\Packetery\Checkout\Model\Log::CREATED_AT . ' < ?' => $thresholdUtc]
        );
    }
}
