<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit\OrderCollection;

use Packetery\Checkout\Model\Address;
use Packetery\Checkout\Model\Carrier\Facade;
use Packetery\Checkout\Model\OrderCollection\OrderCollectionRowBuilder;
use Packetery\Checkout\Model\Pricing\Service;

class OrderCollectionRowBuilderTest extends \Packetery\Checkout\Test\BaseTest
{
    public function testPickupPointOrderReturnsPointName(): void
    {
        $row = $this->createBuilder()->build(
            $this->createPacketeryOrder(
                [
                    'order_number' => '100000001',
                    'point_name' => 'AlzaBox Vyšehrad',
                    'recipient_firstname' => 'Jan',
                    'recipient_lastname' => 'Novák',
                    'country_id' => 'CZ',
                    'cod' => 199.0,
                    'currency' => 'CZK',
                ]
            ),
            $this->createPacket('Z-123'),
            $this->createMagentoOrder('packetery_pickupPointDelivery', '2026-05-19 14:15:30')
        );

        $this->assertSame('100000001', $row->getOrderNumber());
        $this->assertSame('Z-123', $row->getPacketNumber());
        $this->assertSame('Jan Novák', $row->getRecipientName());
        $this->assertSame('AlzaBox Vyšehrad', $row->getDeliveryDestination());
        $this->assertSame(199.0, $row->getCod());
        $this->assertSame('CZK', $row->getCurrency());
        $this->assertNotNull($row->getCreatedAt());
        $this->assertSame('2026-05-19', $row->getCreatedAt()->format('Y-m-d'));
    }

    public function testNonPacketeryShippingMethodFallsBackToPointName(): void
    {
        $row = $this->createBuilder()->build(
            $this->createPacketeryOrder(['point_name' => 'Box 12', 'country_id' => 'CZ']),
            $this->createPacket('Z-456'),
            $this->createMagentoOrder('flatrate_flatrate', '2026-05-19 09:00:00')
        );

        $this->assertSame('Box 12', $row->getDeliveryDestination());
    }

    public function testExternalCarrierUsesPricingRuleName(): void
    {
        $pricingRule = $this->createMock(\Packetery\Checkout\Model\Pricingrule::class);
        $pricingRule->method('getCarrierName')->willReturn('DPD Local');

        $pricingService = $this->createMock(Service::class);
        $pricingService->method('resolvePricingRule')->willReturn($pricingRule);

        $row = (new OrderCollectionRowBuilder($this->createMock(Facade::class), $pricingService))->build(
            $this->createPacketeryOrder(['country_id' => 'DE']),
            $this->createPacket('Z-789'),
            $this->createMagentoOrder('packeteryPacketaDynamic_123-addressDelivery', '2026-05-19 09:00:00')
        );

        $this->assertSame('DPD Local', $row->getDeliveryDestination());
    }

    public function testExternalCarrierFallsBackToHybridCarrierName(): void
    {
        $pricingService = $this->createMock(Service::class);
        $pricingService->method('resolvePricingRule')->willReturn(null);

        $hybridCarrier = $this->createMock(\Packetery\Checkout\Model\HybridCarrier::class);
        $hybridCarrier->method('getFinalCarrierName')->willReturn('GLS');

        $facade = $this->createMock(Facade::class);
        $facade->method('createHybridCarrierCached')->willReturn($hybridCarrier);

        $row = (new OrderCollectionRowBuilder($facade, $pricingService))->build(
            $this->createPacketeryOrder(['country_id' => 'AT']),
            $this->createPacket('Z-321'),
            $this->createMagentoOrder('packeteryPacketaDynamic_456-addressDelivery', '2026-05-19 09:00:00')
        );

        $this->assertSame('GLS', $row->getDeliveryDestination());
    }

    public function testNullCodFallsBackToZero(): void
    {
        $row = $this->createBuilder()->build(
            $this->createPacketeryOrder(['cod' => null, 'currency' => 'CZK']),
            $this->createPacket('Z-000'),
            $this->createMagentoOrder('packetery_pickupPointDelivery', '2026-05-19 09:00:00')
        );

        $this->assertSame(0.0, $row->getCod());
        $this->assertSame('CZK', $row->getCurrency());
    }

    public function testInvalidCreatedAtReturnsNull(): void
    {
        $row = $this->createBuilder()->build(
            $this->createPacketeryOrder([]),
            $this->createPacket('Z-111'),
            $this->createMagentoOrder('packetery_pickupPointDelivery', '')
        );

        $this->assertNull($row->getCreatedAt());
    }

    private function createBuilder(): OrderCollectionRowBuilder
    {
        return new OrderCollectionRowBuilder(
            $this->createMock(Facade::class),
            $this->createMock(Service::class)
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createPacketeryOrder(array $data): \Packetery\Checkout\Model\Order
    {
        $defaults = [
            'order_number' => '100000000',
            'point_name' => '',
            'recipient_firstname' => '',
            'recipient_lastname' => '',
            'cod' => null,
            'currency' => null,
            'country_id' => '',
        ];
        $merged = array_merge($defaults, $data);

        $address = new Address();
        $address->setCountryId((string) $merged['country_id']);

        $order = $this->getMockBuilder(\Packetery\Checkout\Model\Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'getOrderNumber',
                    'getPointName',
                    'getRecipientFirstname',
                    'getRecipientLastname',
                    'getRecipientAddress',
                    'getCod',
                    'getCurrency',
                ]
            )
            ->getMock();
        $order->method('getOrderNumber')->willReturn((string) $merged['order_number']);
        $order->method('getPointName')->willReturn((string) $merged['point_name']);
        $order->method('getRecipientFirstname')->willReturn((string) $merged['recipient_firstname']);
        $order->method('getRecipientLastname')->willReturn((string) $merged['recipient_lastname']);
        $order->method('getRecipientAddress')->willReturn($address);
        $order->method('getCod')->willReturn($merged['cod'] === null ? null : (float) $merged['cod']);
        $order->method('getCurrency')->willReturn($merged['currency'] === null ? null : (string) $merged['currency']);

        return $order;
    }

    private function createPacket(string $packetNumber): \Packetery\Checkout\Model\Packet
    {
        $packet = $this->getMockBuilder(\Packetery\Checkout\Model\Packet::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPacketNumber'])
            ->getMock();
        $packet->method('getPacketNumber')->willReturn($packetNumber);

        return $packet;
    }

    private function createMagentoOrder(string $shippingMethod, string $createdAt): \Magento\Sales\Model\Order
    {
        $order = $this->createMock(\Magento\Sales\Model\Order::class);
        $order->method('getShippingMethod')->willReturn($shippingMethod);
        $order->method('getCreatedAt')->willReturn($createdAt);

        return $order;
    }
}
