<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\ResourceModel\Log;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'packetery_checkout_log_collection';
    protected $_eventObject = 'log_collection';

    protected function _construct(): void
    {
        $this->_init('Packetery\Checkout\Model\Log', 'Packetery\Checkout\Model\ResourceModel\Log');
    }
}
