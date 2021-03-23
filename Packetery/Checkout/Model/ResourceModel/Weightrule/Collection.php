<?php
namespace Packetery\Checkout\Model\ResourceModel\Weightrule;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'packetery_checkout_pricingrule_collection';
    protected $_eventObject = 'pricingrule_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Packetery\Checkout\Model\Weightrule', 'Packetery\Checkout\Model\ResourceModel\Weightrule');
    }

    /**
     * @return \Packetery\Checkout\Model\Weightrule[]
     */
    public function getItems()
    {
        return parent::getItems();
    }
}
