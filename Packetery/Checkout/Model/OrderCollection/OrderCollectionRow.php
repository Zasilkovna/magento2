<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\OrderCollection;

class OrderCollectionRow
{
    private string $orderNumber;

    private string $packetNumber;

    private ?\DateTimeImmutable $createdAt;

    private string $recipientName;

    private string $deliveryDestination;

    private float $cod;

    private string $currency;

    private ?string $consignPassword;

    public function __construct(
        string $orderNumber,
        string $packetNumber,
        ?\DateTimeImmutable $createdAt,
        string $recipientName,
        string $deliveryDestination,
        float $cod,
        string $currency,
        ?string $consignPassword
    ) {
        $this->orderNumber = $orderNumber;
        $this->packetNumber = $packetNumber;
        $this->createdAt = $createdAt;
        $this->recipientName = $recipientName;
        $this->deliveryDestination = $deliveryDestination;
        $this->cod = $cod;
        $this->currency = $currency;
        $this->consignPassword = $consignPassword;
    }

    public function getOrderNumber(): string
    {
        return $this->orderNumber;
    }

    public function getPacketNumber(): string
    {
        return $this->packetNumber;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getRecipientName(): string
    {
        return $this->recipientName;
    }

    public function getDeliveryDestination(): string
    {
        return $this->deliveryDestination;
    }

    public function getCod(): float
    {
        return $this->cod;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getConsignPassword(): ?string
    {
        return $this->consignPassword;
    }
}
