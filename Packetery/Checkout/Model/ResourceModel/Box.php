<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Packetery\Checkout\Model\Box as BoxModel;

class Box extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init(BoxModel::TABLE_NAME, BoxModel::ID);
    }
}
