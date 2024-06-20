<?php

namespace Packetery\Checkout\Model;

class Packetdraft extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    public const CACHE_TAG = 'packetery_checkout_packetdraft';

    protected $_cacheTag = 'packetery_checkout_packetdraft';

    protected $_eventPrefix = 'packetery_checkout_packetdraft';

    protected function _construct()
    {
        $this->_init('Packetery\Checkout\Model\ResourceModel\Packetdraft');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
}
