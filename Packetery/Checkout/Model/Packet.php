<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model;

class Packet extends \Magento\Framework\Model\AbstractModel
{
    protected $_eventPrefix = 'packetery_packet';

    protected function _construct(): void
    {
        $this->_init(\Packetery\Checkout\Model\ResourceModel\Packet::class);
    }

    public function getOrderNumber(): string
    {
        return (string) $this->getData('order_number');
    }

    public function getPacketNumber(): string
    {
        return (string) $this->getData('packet_number');
    }

    public function getWeight(): float
    {
        return (float) $this->getData('weight');
    }

    public function getValue(): float
    {
        return (float) $this->getData('value');
    }

    public function getCod(): float
    {
        return (float) $this->getData('cod');
    }

    public function setOrderNumber(string $orderNumber): self
    {
        $this->setData('order_number', $orderNumber);
        return $this;
    }

    public function setPacketNumber(string $packetNumber): self
    {
        $this->setData('packet_number', $packetNumber);
        return $this;
    }

    public function setWeight(float $weight): self
    {
        $this->setData('weight', $weight);
        return $this;
    }

    public function setValue(float $value): self
    {
        $this->setData('value', $value);
        return $this;
    }

    public function setCod(float $cod): self
    {
        $this->setData('cod', $cod);
        return $this;
    }
}
