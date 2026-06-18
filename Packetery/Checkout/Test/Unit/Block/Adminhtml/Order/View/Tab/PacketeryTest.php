<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit\Block\Adminhtml\Order\View\Tab;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Packetery\Checkout\Block\Adminhtml\Order\View\Tab\Packetery;
use Packetery\Checkout\Model\Dimensions\Converter;
use Packetery\Checkout\Model\Packet;
use Packetery\Checkout\Model\Packet\TrackingUrlFactory;
use Packetery\Checkout\Model\PacketRepository;
use Packetery\Checkout\Model\ResourceModel\Order\Collection;
use Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory;
use Packetery\Checkout\Test\BaseTest;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

#[AllowMockObjectsWithoutExpectations]
class PacketeryTest extends BaseTest
{
    private const PACKET_NUMBER = '1234567890';
    private const TRACKING_NUMBER = 'Z' . self::PACKET_NUMBER;
    private const TRACKING_URL = 'https://tracking.packeta.com/' . self::TRACKING_NUMBER;

    /**
     * Read-only age verification must be offered exactly where the edit form offers it:
     * own pickup-point delivery into a Packeta base country, never on an external carrier's
     * pickup point (which does not accept the adultContent attribute) and nowhere else.
     */
    #[DataProvider('adultContentEligibilityProvider')]
    public function testIsAdultContentEligibleMatchesEditFormCondition(
        bool $hasOrder,
        ?string $shippingMethod,
        ?string $countryId,
        bool $isCarrier,
        bool $expected
    ): void {
        $order = $hasOrder ? $this->prepareOrderMock($shippingMethod, $countryId) : null;

        $packeteryOrder = $this->createMock(\Packetery\Checkout\Model\Order::class);
        $packeteryOrder->method('isCarrier')
            ->willReturn($isCarrier);

        $block = $this->createProxy(Packetery::class, [
            'magentoOrder' => $order,
            'magentoOrderLoaded' => true,
            'packeteryOrder' => $packeteryOrder,
            'packeteryOrderLoaded' => true,
        ]);

        $this->assertSame($expected, $block->isAdultContentEligible());
    }

    /**
     * @return array<string, array{bool, string|null, string|null, bool, bool}>
     */
    public static function adultContentEligibilityProvider(): array
    {
        return [
            'own pickup-point + CZ → eligible' => [true, 'packetery_pickupPointDelivery', 'CZ', false, true],
            'own pickup-point + SK → eligible' => [true, 'packetery_pickupPointDelivery', 'SK', false, true],
            'carrier pickup-point + CZ → not eligible' => [true, 'packetery_pickupPointDelivery', 'CZ', true, false],
            'pickup-point + UA → not eligible (non-base country)' => [true, 'packetery_pickupPointDelivery', 'UA', false, false],
            'address delivery (HD) + CZ → not eligible' => [true, 'packeteryPacketaDynamic_106-directAddressDelivery', 'CZ', false, false],
            'non-packetery method → not eligible' => [true, 'flatrate_flatrate', 'CZ', false, false],
            'no shipping address → not eligible' => [true, 'packetery_pickupPointDelivery', null, false, false],
            'no order → not eligible' => [false, null, null, false, false],
        ];
    }

    /**
     * The read-only view is shown exactly when a packet exists for the order (after submission)
     */
    public function testIsSubmittedReflectsPacketPresence(): void
    {
        $withPacket = $this->createProxy(
            Packetery::class,
            [
                'packet' => $this->createStub(Packet::class),
                'packetLoaded' => true,
            ]
        );
        $withoutPacket = $this->createProxy(
            Packetery::class,
            [
                'packet' => null,
                'packetLoaded' => true,
            ]
        );

        $this->assertTrue($withPacket->isSubmitted());
        $this->assertFalse($withoutPacket->isSubmitted());
    }

    /**
     * The other tests inject the post-load cache; here the lazy loaders run for real: order_id from
     * the request to orderRepository->get, then the order_number collection lookup yields the order row
     *
     * @throws \ReflectionException
     */
    public function testGetPacketeryOrderLoadsThroughCollection(): void
    {
        $row = $this->createStub(\Packetery\Checkout\Model\Order::class);
        $row->method('getId')
            ->willReturn(5);

        $block = $this->createProxy(
            Packetery::class,
            $this->loaderProperties($this->prepareLoadableOrder(), $row)
        );

        $this->assertSame(5, $block->getPacketeryOrderId());
    }

    /**
     * The collection returns a blank row when no packetery_order matches; its falsy id must be
     * treated as "no order", not as a real row
     *
     * @throws \ReflectionException
     */
    public function testGetPacketeryOrderTreatsEmptyRowAsNoOrder(): void
    {
        $emptyRow = $this->createStub(\Packetery\Checkout\Model\Order::class);
        $emptyRow->method('getId')
            ->willReturn(null);

        $block = $this->createProxy(
            Packetery::class,
            $this->loaderProperties($this->prepareLoadableOrder(), $emptyRow)
        );

        $this->assertNull($block->getPacketeryOrder());
        $this->assertNull($block->getPacketeryOrderId());
    }

    /**
     * A deleted Magento order makes orderRepository->get throw; the loader swallows it and the tab
     * degrades to "no order" instead of bubbling the exception
     *
     * @throws \ReflectionException
     */
    public function testGetOrderSwallowsMissingMagentoOrder(): void
    {
        $request = $this->createStub(RequestInterface::class);
        $request->method('getParam')
            ->willReturn('5');

        $orderRepository = $this->createStub(OrderRepositoryInterface::class);
        $orderRepository->method('get')
            ->willThrowException(new NoSuchEntityException());

        $block = $this->createProxy(
            Packetery::class,
            [
                '_request' => $request,
                'orderRepository' => $orderRepository,
            ]
        );

        $this->assertNull($block->getPacketeryOrder());
    }

    /**
     * isSubmitted driven through the real getPacket chain (collection row to
     * packetRepository->findLatestByOrderNumber), not the injected packet cache
     *
     * @throws \ReflectionException
     */
    #[DataProvider('packetPresenceProvider')]
    public function testIsSubmittedLoadsPacketThroughRepository(bool $hasPacket, bool $expected): void
    {
        $row = $this->createStub(\Packetery\Checkout\Model\Order::class);
        $row->method('getId')
            ->willReturn(5);
        $row->method('getOrderNumber')
            ->willReturn('000000004');

        $packetRepository = $this->createStub(PacketRepository::class);
        $packetRepository->method('findLatestByOrderNumber')
            ->willReturn($hasPacket ? $this->createStub(Packet::class) : null);

        $properties = $this->loaderProperties($this->prepareLoadableOrder(), $row);
        $properties['packetRepository'] = $packetRepository;

        $block = $this->createProxy(Packetery::class, $properties);

        $this->assertSame($expected, $block->isSubmitted());
    }

    /**
     * @return array<string, array{bool, bool}>
     */
    public static function packetPresenceProvider(): array
    {
        return [
            'packet found is submitted' => [true, true],
            'no packet is not submitted' => [false, false],
        ];
    }

    /**
     * After submission the packet number becomes the Z-prefixed tracking link;
     * with no packet there is nothing to link
     */
    public function testTrackingUrlAndNumberComeFromPacket(): void
    {
        $packet = $this->createStub(Packet::class);
        $packet->method('getPacketNumber')
            ->willReturn(self::PACKET_NUMBER);

        $trackingUrlFactory = $this->createStub(TrackingUrlFactory::class);
        $trackingUrlFactory->method('create')
            ->willReturn(self::TRACKING_URL);
        $trackingUrlFactory->method('formatNumber')
            ->willReturn(self::TRACKING_NUMBER);

        $block = $this->createProxy(
            Packetery::class,
            [
                'packet' => $packet,
                'packetLoaded' => true,
                'trackingUrlFactory' => $trackingUrlFactory,
            ]
        );

        $this->assertSame(self::TRACKING_URL, $block->getTrackingUrl());
        $this->assertSame(self::TRACKING_NUMBER, $block->getTrackingNumber());

        $noPacket = $this->createProxy(
            Packetery::class,
            [
                'packet' => null,
                'packetLoaded' => true,
                'trackingUrlFactory' => $trackingUrlFactory,
            ]
        );
        $this->assertNull($noPacket->getTrackingUrl());
        $this->assertNull($noPacket->getTrackingNumber());
    }

    /**
     * The read-only dimensions come from the packet snapshot and render only when complete;
     * any missing snapshot dimension (or no packet) yields no label
     */
    #[DataProvider('dimensionsLabelProvider')]
    public function testGetDimensionsLabel(
        bool $hasPacket,
        ?string $name,
        ?float $depth,
        ?float $width,
        ?float $height,
        ?string $expected
    ): void {
        $properties = [
            'packetLoaded' => true,
            'dimensionsConverter' => new Converter(),
        ];

        if ($hasPacket) {
            $packet = $this->createStub(Packet::class);
            $packet->method('getBoxName')
                ->willReturn($name);
            $packet->method('getBoxDepth')
                ->willReturn($depth);
            $packet->method('getBoxWidth')
                ->willReturn($width);
            $packet->method('getBoxHeight')
                ->willReturn($height);
            $properties['packet'] = $packet;
        } else {
            $properties['packet'] = null;
        }

        $block = $this->createProxy(Packetery::class, $properties);

        $this->assertSame($expected, $block->getDimensionsLabel());
    }

    /**
     * @return array<string, array{bool, ?string, ?float, ?float, ?float, ?string}>
     */
    public static function dimensionsLabelProvider(): array
    {
        return [
            'complete snapshot renders label' => [
                true,
                'M',
                30.0,
                20.0,
                10.0,
                'M (30 × 20 × 10 cm)',
            ],
            'missing depth yields no label' => [
                true,
                'M',
                null,
                20.0,
                10.0,
                null,
            ],
            'missing name yields no label' => [
                true,
                null,
                30.0,
                20.0,
                10.0,
                null,
            ],
            'no packet yields no label' => [
                false,
                null,
                null,
                null,
                null,
                null,
            ],
        ];
    }

    /**
     * On a pickup-point order the delivery label is the chosen point name
     */
    public function testGetDeliveryLabelForPickupPoint(): void
    {
        $magentoOrder = $this->prepareOrderMock(self::SHIPPING_PICKUP_POINT);

        $packeteryOrder = $this->createStub(\Packetery\Checkout\Model\Order::class);
        $packeteryOrder->method('getPointName')
            ->willReturn('Praha 1, Václavské náměstí');

        $block = $this->createProxy(
            Packetery::class,
            [
                'magentoOrder' => $magentoOrder,
                'magentoOrderLoaded' => true,
                'packeteryOrder' => $packeteryOrder,
                'packeteryOrderLoaded' => true,
            ]
        );

        $this->assertSame('Praha 1, Václavské náměstí', $block->getDeliveryLabel());
        $this->assertSame('Pick-up point', $block->getDeliveryLabelHeading());
    }

    /**
     * On an address-delivery order the label is the recipient address (filtered, comma-joined)
     * and the heading switches to "Delivery address"
     */
    public function testGetDeliveryLabelForAddress(): void
    {
        $magentoOrder = $this->prepareOrderMock(self::SHIPPING_ADDRESS_DELIVERY);

        $address = $this->createStub(\Packetery\Checkout\Model\Address::class);
        $address->method('getStreet')
            ->willReturn('Václavské náměstí');
        $address->method('getHouseNumber')
            ->willReturn('1');
        $address->method('getCity')
            ->willReturn('Praha');
        $address->method('getZip')
            ->willReturn('11000');

        $packeteryOrder = $this->createStub(\Packetery\Checkout\Model\Order::class);
        $packeteryOrder->method('getRecipientAddress')
            ->willReturn($address);

        $block = $this->createProxy(
            Packetery::class,
            [
                'magentoOrder' => $magentoOrder,
                'magentoOrderLoaded' => true,
                'packeteryOrder' => $packeteryOrder,
                'packeteryOrderLoaded' => true,
            ]
        );

        $this->assertSame('Václavské náměstí, 1, Praha, 11000', $block->getDeliveryLabel());
        $this->assertSame('Delivery address', $block->getDeliveryLabelHeading());
    }

    /**
     * The tab renders only for a Packeta order: a non-Packeta shipping method or an order
     * with no packetery_order row hides it, and there is nothing to show with no order at all
     *
     * @throws \ReflectionException
     */
    #[DataProvider('canShowTabProvider')]
    public function testCanShowTab(
        bool $hasOrder,
        ?string $shippingMethod,
        bool $hasPacketeryOrder,
        bool $expected
    ): void {
        $order = $hasOrder ? $this->prepareOrderMock($shippingMethod) : null;

        $packeteryOrder = null;
        if ($hasPacketeryOrder) {
            $packeteryOrder = $this->createStub(\Packetery\Checkout\Model\Order::class);
            $packeteryOrder->method('getId')
                ->willReturn(5);
        }

        $block = $this->createProxy(
            Packetery::class,
            [
                'magentoOrder' => $order,
                'magentoOrderLoaded' => true,
                'packeteryOrder' => $packeteryOrder,
                'packeteryOrderLoaded' => true,
            ]
        );

        $this->assertSame($expected, $block->canShowTab());
    }

    /**
     * @return array<string, array{bool, ?string, bool, bool}>
     */
    public static function canShowTabProvider(): array
    {
        return [
            'packeta order with row → shown' => [
                true,
                self::SHIPPING_PICKUP_POINT,
                true,
                true,
            ],
            'packeta method but no packetery row → hidden' => [
                true,
                self::SHIPPING_PICKUP_POINT,
                false,
                false,
            ],
            'non-packeta method → hidden' => [
                true,
                self::SHIPPING_NON_PACKETERY,
                true,
                false,
            ],
            'empty shipping method → hidden' => [
                true,
                '',
                true,
                false,
            ],
            'no order → hidden' => [
                false,
                null,
                false,
                false,
            ],
        ];
    }

    /**
     * Magento order resolvable through the request/orderRepository loader
     *
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    private function prepareLoadableOrder(): \Magento\Sales\Model\Order&MockObject
    {
        $order = $this->prepareOrderMock(self::SHIPPING_PICKUP_POINT);
        $order->method('getIncrementId')
            ->willReturn('000000004');

        return $order;
    }

    /**
     * Properties that wire the lazy loaders: order_id from the request to orderRepository->get,
     * then the order_number collection lookup returning the given first row
     *
     * @return array<string, mixed>
     */
    private function loaderProperties(
        \Magento\Sales\Api\Data\OrderInterface $magentoOrder,
        \Packetery\Checkout\Model\Order $firstRow
    ): array {
        $request = $this->createStub(RequestInterface::class);
        $request->method('getParam')
            ->willReturn('5');

        $orderRepository = $this->createStub(OrderRepositoryInterface::class);
        $orderRepository->method('get')
            ->willReturn($magentoOrder);

        $collection = $this->createStub(Collection::class);
        $collection->method('getFirstItem')
            ->willReturn($firstRow);

        $collectionFactory = $this->createStub(CollectionFactory::class);
        $collectionFactory->method('create')
            ->willReturn($collection);

        return [
            '_request' => $request,
            'orderRepository' => $orderRepository,
            'orderCollectionFactory' => $collectionFactory,
        ];
    }
}
