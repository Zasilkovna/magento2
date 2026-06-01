<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\OrderCollection;

use Packetery\Checkout\Model\Carrier\Imp\PacketeryPacketaDynamic\Brain as DynamicBrain;
use Packetery\Checkout\Model\Carrier\ShippingRateCode;

class OrderCollectionRowBuilder
{
    private \Packetery\Checkout\Model\Carrier\Facade $carrierFacade;

    private \Packetery\Checkout\Model\Pricing\Service $pricingService;

    public function __construct(
        \Packetery\Checkout\Model\Carrier\Facade $carrierFacade,
        \Packetery\Checkout\Model\Pricing\Service $pricingService
    ) {
        $this->carrierFacade = $carrierFacade;
        $this->pricingService = $pricingService;
    }

    public function build(
        \Packetery\Checkout\Model\Order $packeteryOrder,
        \Packetery\Checkout\Model\Packet $packet,
        \Magento\Sales\Model\Order $magentoOrder
    ): OrderCollectionRow {
        $recipientCountry = (string) $packeteryOrder->getRecipientAddress()->getCountryId();
        $recipientName = trim($packeteryOrder->getRecipientFirstname() . ' ' . $packeteryOrder->getRecipientLastname());

        return new OrderCollectionRow(
            $packeteryOrder->getOrderNumber(),
            $packet->getPacketNumber(),
            $this->parseCreatedAt((string) $magentoOrder->getCreatedAt()),
            $recipientName,
            $this->resolveDeliveryDestination($packeteryOrder, $magentoOrder, $recipientCountry),
            (float) ($packeteryOrder->getCod() ?? 0.0),
            (string) ($packeteryOrder->getCurrency() ?? ''),
            $packet->getConsignPassword()
        );
    }

    private function resolveDeliveryDestination(
        \Packetery\Checkout\Model\Order $packeteryOrder,
        \Magento\Sales\Model\Order $magentoOrder,
        string $recipientCountry
    ): string {
        $shippingMethod = (string) $magentoOrder->getShippingMethod();
        if (ShippingRateCode::isPacketery($shippingMethod) === false) {
            return $packeteryOrder->getPointName();
        }

        $shippingRate = ShippingRateCode::fromString($shippingMethod);
        $isExternalCarrier = $shippingRate->getCarrierCode() === DynamicBrain::getCarrierCodeStatic();
        if ($isExternalCarrier === false) {
            return $packeteryOrder->getPointName();
        }

        $methodCode = $shippingRate->getMethodCode();
        $pricingRule = $this->pricingService->resolvePricingRule(
            $methodCode->getMethod(),
            $recipientCountry,
            $shippingRate->getCarrierCode(),
            $methodCode->getDynamicCarrierId()
        );
        $carrierName = $pricingRule !== null ? $pricingRule->getCarrierName() : null;
        if ($carrierName !== null && $carrierName !== '') {
            return $carrierName;
        }

        $cache = [];
        $hybridCarrier = $this->carrierFacade->createHybridCarrierCached(
            $cache,
            $shippingRate->getCarrierCode(),
            $methodCode->getDynamicCarrierId(),
            $methodCode->getMethod(),
            $recipientCountry
        );

        return (string) $hybridCarrier->getFinalCarrierName();
    }

    private function parseCreatedAt(string $createdAt): ?\DateTimeImmutable
    {
        if ($createdAt === '') {
            return null;
        }

        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $createdAt, new \DateTimeZone('UTC'));
        if ($parsed === false) {
            return null;
        }

        return $parsed;
    }
}
