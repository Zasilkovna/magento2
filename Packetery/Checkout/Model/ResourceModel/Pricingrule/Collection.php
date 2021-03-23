<?php
namespace Packetery\Checkout\Model\ResourceModel\Pricingrule;

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
        $this->_init('Packetery\Checkout\Model\Pricingrule', 'Packetery\Checkout\Model\ResourceModel\Pricingrule');
    }

    /**
     * @return \Packetery\Checkout\Model\Pricingrule[]
     */
    public function getItems()
    {
        return parent::getItems();
    }

    public function getFirstRecord(): ?\Packetery\Checkout\Model\Pricingrule
    {
        $this->load();
        $items = $this->_items;
        return array_shift($items);
    }
}
