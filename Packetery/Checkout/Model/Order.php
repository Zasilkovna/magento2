<?php
namespace Packetery\Checkout\Model;

class Order extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'packetery_checkout_order';

    protected $_cacheTag = 'packetery_checkout_order';

    protected $_eventPrefix = 'packetery_checkout_order';

    protected function _construct()
    {
        $this->_init('Packetery\Checkout\Model\ResourceModel\Order');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getDefaultValues()
    {
        $values = [];

        return $values;
    }

    /**
     * @return bool
     */
    public function isAddressValidated(): bool {
        return $this->getData('address_validated') === '1';
    }

    /**
     * @return \Packetery\Checkout\Model\Address
     */
    public function getRecipientAddress(): Address {
        $address = new Address();
        $address->setStreet($this->getData('recipient_street'));
        $address->setHouseNumber($this->getData('recipient_house_number'));
        $address->setCity($this->getData('recipient_city'));
        $address->setZip($this->getData('recipient_zip'));
        $address->setCounty($this->getData('recipient_county'));
        $address->setLongitude($this->getData('recipient_longitude'));
        $address->setLatitude($this->getData('recipient_latitude'));
        // countryId is not filled
        return $address;
    }
}
