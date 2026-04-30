<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model;

use DateTimeImmutable;
use DateTimeZone;

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

    public function getCourierNumber(): ?string
    {
        $value = $this->getData('courier_number');
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    public function setCourierNumber(?string $courierNumber): self
    {
        $this->setData('courier_number', $courierNumber);
        return $this;
    }

    public function getLabelPrintedAt(): ?DateTimeImmutable
    {
        $value = $this->getData('label_printed_at');
        if ($value === null || $value === '') {
            return null;
        }

        $parsed = DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            trim((string) $value),
            new DateTimeZone('UTC')
        );
        if ($parsed === false) {
            return null;
        }

        return $parsed;
    }

    public function setLabelPrintedAt(?DateTimeImmutable $dateTime): self
    {
        if ($dateTime === null) {
            $this->setData('label_printed_at', null);
            return $this;
        }

        $utc = $dateTime->setTimezone(new DateTimeZone('UTC'));
        $this->setData('label_printed_at', $utc->format('Y-m-d H:i:s'));
        return $this;
    }
}
