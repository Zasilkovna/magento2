<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\ResourceModel\Box;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Packetery\Checkout\Model\Box;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'id';

    protected function _construct(): void
    {
        $this->_init(
            Box::class,
            \Packetery\Checkout\Model\ResourceModel\Box::class
        );
    }
}
