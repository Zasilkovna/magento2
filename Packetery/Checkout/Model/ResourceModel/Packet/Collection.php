<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\ResourceModel\Packet;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';

    protected function _construct(): void
    {
        $this->_init(
            \Packetery\Checkout\Model\Packet::class,
            \Packetery\Checkout\Model\ResourceModel\Packet::class
        );
    }
}
