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

    public function getPointId(): int
    {
        return (int) $this->getData('point_id');
    }

    public function getPointName(): string
    {
        return (string) $this->getData('point_name');
    }

    public function getOrderNumber(): string
    {
        return $this->getData('order_number');
    }

    public function isAddressValidated(): bool
    {
        return $this->getData('address_validated') === '1';
    }

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

    public function getRecipientFirstname(): string
    {
        return (string) $this->getData('recipient_firstname');
    }

    public function getRecipientLastname(): string
    {
        return (string) $this->getData('recipient_lastname');
    }

    public function getRecipientCompany(): string
    {
        return (string) $this->getData('recipient_company');
    }

    public function getRecipientEmail(): string
    {
        return (string) $this->getData('recipient_email');
    }

    public function getRecipientPhone(): string
    {
        return (string) $this->getData('recipient_phone');
    }

    public function isCarrier(): bool
    {
        return $this->getData('is_carrier') === '1';
    }

    public function getCarrierPickupPoint(): ?string
    {
        $value = $this->getData('carrier_pickup_point');
        if ($value === null) {
            return null;
        }

        return (string) $value;
    }

    public function getWeight(): ?float
    {
        $value = $this->getData('weight');
        if (!is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    public function getValue(): ?float
    {
        $value = $this->getData('value');
        if (!is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    public function getCod(): ?float
    {
        $value = $this->getData('cod');
        if (!is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    public function getCurrency(): ?string
    {
        $value = $this->getData('currency');
        if ($value === null) {
            return null;
        }

        return (string) $value;
    }

    public function markExported(): void
    {
        $this->setData('exported', 1);
        $this->setData('exported_at', (new \DateTime())->format('Y-m-d H:i:s'));
    }
}
