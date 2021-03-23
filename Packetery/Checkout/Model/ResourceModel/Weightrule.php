<?php
namespace Packetery\Checkout\Model\ResourceModel;

class Weightrule extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context
    ) {
        parent::__construct($context);
    }

    protected function _construct()
    {
        $this->_init('packetery_weight_rule', 'id');
    }
}
