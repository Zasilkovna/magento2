<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\ResourceModel\Packetdraft;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';

    protected $_eventPrefix = 'packetery_checkout_packetdraft_collection';

    protected $_eventObject = 'packetdraft_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Packetery\Checkout\Model\Packetdraft', 'Packetery\Checkout\Model\ResourceModel\Packetdraft');
    }

    /**
     * @return \Packetery\Checkout\Model\Packetdraft[]
     */
    public function getItems(): array
    {
        return parent::getItems();
    }
}
