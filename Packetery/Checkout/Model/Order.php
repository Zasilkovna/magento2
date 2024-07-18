<?php

namespace Packetery\Checkout\Model;

class Order extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    public const CACHE_TAG = 'packetery_checkout_order';

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
     * @return int
     */
    public function getPointId(): int
    {
        return $this->getData('point_id');
    }

    /**
     * @return string
     */
    public function getPointName(): string
    {
        return $this->getData('point_name');
    }

    /**
     * @return bool
     */
    public function isAddressValidated(): bool
    {
        return $this->getData('address_validated');
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return (int)parent::getId();
    }

    /**
     * @return float|null
     */
    public function getCod(): ?float
    {
        return $this->getData('cod');
    }

    /**
     * @return string|null
     */
    public function getCurrency(): ?string
    {
        return $this->getData('currency');
    }

    /**
     * @return float|null
     */
    public function getValue(): ?float
    {
        return $this->getData('value');
    }

    /**
     * @return float|null
     */
    public function getWeight(): ?float
    {
        return $this->getData('weight');
    }

    /**
     * @return bool|null
     */
    public function hasAdultContent(): ?bool
    {
        return $this->getData('adult_content');
    }

    /**
     * @return string|null
     */
    public function getPlannedDispatch(): ?string
    {
        return $this->getData('delayed_delivery');
    }

    /**
     * @return int|null
     */
    public function getWidth(): ?int
    {
        return $this->getData('width');
    }

    /**
     * @return int|null
     */
    public function getHeight(): ?int
    {
        return $this->getData('height');
    }

    /**
     * @return int|null
     */
    public function getLength(): ?int
    {
        return $this->getData('depth');
    }

    /**
     * @return \Packetery\Checkout\Model\Address
     */
    public function getRecipientAddress(): Address
    {
        $address = new Address();
        $address->setStreet($this->getData('recipient_street'));
        $address->setHouseNumber($this->getData('recipient_house_number'));
        $address->setCity($this->getData('recipient_city'));
        $address->setZip($this->getData('recipient_zip'));
        $address->setCountryId($this->getData('recipient_country_id'));
        $address->setCounty($this->getData('recipient_county'));
        $address->setLongitude($this->getData('recipient_longitude'));
        $address->setLatitude($this->getData('recipient_latitude'));

        return $address;
    }
}
