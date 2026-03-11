<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\ResourceModel;

class Packet extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('packetery_packet', 'id');
    }
}
