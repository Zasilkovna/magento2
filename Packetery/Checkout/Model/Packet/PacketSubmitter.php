<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Packet;

use Packetery\Checkout\Model\Carrier\Methods;
use Packetery\Checkout\Model\Carrier\ShippingRateCode;

class PacketSubmitter
{
    /** @var \Packetery\Checkout\Model\Api\SoapApiClient */
    private $soapApiClient;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    private $scopeConfig;

    /** @var \Packetery\Checkout\Model\Weight\Calculator */
    private $weightCalculator;

    /** @var \Packetery\Checkout\Model\PacketFactory */
    private $packetFactory;

    /** @var \Packetery\Checkout\Model\ResourceModel\Packet\CollectionFactory */
    private $packetCollectionFactory;

    /** @var \Packetery\Checkout\Model\ResourceModel\Packet */
    private $packetResource;

    /** @var \Packetery\Checkout\Model\ResourceModel\Order */
    private $orderResource;

    public function __construct(
        \Packetery\Checkout\Model\Api\SoapApiClient $soapApiClient,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Packetery\Checkout\Model\Weight\Calculator $weightCalculator,
        \Packetery\Checkout\Model\PacketFactory $packetFactory,
        \Packetery\Checkout\Model\ResourceModel\Packet\CollectionFactory $packetCollectionFactory,
        \Packetery\Checkout\Model\ResourceModel\Packet $packetResource,
        \Packetery\Checkout\Model\ResourceModel\Order $orderResource
    ) {
        $this->soapApiClient = $soapApiClient;
        $this->scopeConfig = $scopeConfig;
        $this->weightCalculator = $weightCalculator;
        $this->packetFactory = $packetFactory;
        $this->packetCollectionFactory = $packetCollectionFactory;
        $this->packetResource = $packetResource;
        $this->orderResource = $orderResource;
    }

    /**
     * @throws \Packetery\Checkout\Model\Packet\PacketSubmitLocalizedException
     * @throws \Packetery\Checkout\Model\Api\PacketSubmissionException
     */
    public function submitPacket(\Packetery\Checkout\Model\Order $packeteryOrder, \Magento\Sales\Model\Order $magentoOrder): void
    {
        $storeId = (int) $magentoOrder->getStoreId();
        $apiPassword = (string) ($this->scopeConfig->getValue('carriers/packetery/api_password', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId) ?? '');
        $sender = (string) ($this->scopeConfig->getValue('carriers/packetery/sender', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId) ?? '');
        if ($apiPassword === '' || $sender === '') {
            throw new \Packetery\Checkout\Model\Packet\PacketSubmitLocalizedException(
                __('API password and Sender must be configured.')
            );
        }

        if ($this->isAlreadySubmitted($packeteryOrder->getOrderNumber())) {
            throw new \Packetery\Checkout\Model\Packet\PacketSubmitLocalizedException(
                __('This packet has already been submitted to Packeta.')
            );
        }

        $weight = $this->resolveWeight($packeteryOrder, $magentoOrder);

        $value = $packeteryOrder->getValue();
        if ($value === null) {
            $value = (float) $magentoOrder->getGrandTotal();
        }

        $cod = $packeteryOrder->getCod();
        if ($cod === null) {
            $cod = 0.0;
        }

        $currency = $packeteryOrder->getCurrency();
        if ($currency === null) {
            $currency = (string) $magentoOrder->getOrderCurrencyCode();
        }

        $attributes = (new \Packetery\Checkout\Model\Packet\PacketAttributes())
            ->withNumber($packeteryOrder->getOrderNumber())
            ->withName($packeteryOrder->getRecipientFirstname())
            ->withSurname($packeteryOrder->getRecipientLastname())
            ->withCompany($packeteryOrder->getRecipientCompany())
            ->withEmail($packeteryOrder->getRecipientEmail())
            ->withPhone($packeteryOrder->getRecipientPhone())
            ->withAddressId($packeteryOrder->getPointId())
            ->withValue($value)
            ->withCurrency($currency)
            ->withWeight($weight)
            ->withEshop($sender);

        if ($cod > 0.0) {
            $attributes = $attributes->withCod($cod);
        }

        $shippingRateCode = ShippingRateCode::fromString((string) $magentoOrder->getShippingMethod());
        $methodCode = $shippingRateCode->getMethodCode();
        if (Methods::isPickupPointDelivery($methodCode->getMethod())) {
            $carrierPickupPoint = $packeteryOrder->getCarrierPickupPoint();
            if ($carrierPickupPoint !== null) {
                $attributes = $attributes->withCarrierPickupPoint($carrierPickupPoint);
            }
        }

        if (Methods::isAnyAddressDelivery($methodCode->getMethod())) {
            $recipientAddress = $packeteryOrder->getRecipientAddress();
            $attributes = $attributes
                ->withStreet((string) $recipientAddress->getStreet())
                ->withHouseNumber((string) $recipientAddress->getHouseNumber())
                ->withCity((string) $recipientAddress->getCity())
                ->withZip((string) $recipientAddress->getZip());
        }

        $createResult = $this->soapApiClient->createPacket($apiPassword, $attributes);
        $this->savePacket($packeteryOrder->getOrderNumber(), $createResult->getPacketId(), $weight, $value, $cod);
        $this->markOrderExported($packeteryOrder);
    }

    private function isAlreadySubmitted(string $orderNumber): bool
    {
        $collection = $this->packetCollectionFactory->create();
        $collection->addFieldToFilter('order_number', $orderNumber);
        return $collection->getSize() > 0;
    }

    private function resolveWeight(\Packetery\Checkout\Model\Order $packeteryOrder, \Magento\Sales\Model\Order $magentoOrder): float
    {
        $manualWeight = $packeteryOrder->getWeight();
        if ($manualWeight !== null && $manualWeight > 0.0) {
            return $manualWeight;
        }
        return $this->weightCalculator->getOrderWeight($magentoOrder);
    }

    private function savePacket(string $orderNumber, string $packetId, float $weight, float $value, float $cod): void
    {
        $packet = $this->packetFactory->create();
        $packet->setOrderNumber($orderNumber);
        $packet->setPacketNumber($packetId);
        $packet->setWeight($weight);
        $packet->setValue($value);
        $packet->setCod($cod);
        $this->packetResource->save($packet);
    }

    private function markOrderExported(\Packetery\Checkout\Model\Order $packeteryOrder): void
    {
        $packeteryOrder->markExported();
        $this->orderResource->save($packeteryOrder);
    }
}
