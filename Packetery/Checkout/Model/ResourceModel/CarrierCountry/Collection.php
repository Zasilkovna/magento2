<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\ResourceModel\CarrierCountry;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'country';

    protected $_eventPrefix = 'packetery_checkout_carrier_collection';

    protected $_eventObject = 'carrier_collection';

    /**
     * @return void
     */
    protected function _construct() {
        $this->_init('Packetery\Checkout\Model\Carrier', 'Packetery\Checkout\Model\ResourceModel\Carrier');
    }
}
