<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model;

class CarrierCountry extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'packetery_checkout_carrier';

    protected $_cacheTag = 'packetery_checkout_carrier';

    protected $_eventPrefix = 'packetery_checkout_carrier';

    protected function _construct()
    {
        $this->_init('Packetery\Checkout\Model\ResourceModel\Carrier');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getDefaultValues()
    {
        return [];
    }
}
