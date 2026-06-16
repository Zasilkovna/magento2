<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit\Ui\Order;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\OrderFactory;
use Packetery\Checkout\Model\BoxRepository;
use Packetery\Checkout\Model\Carrier\AbstractBrain;
use Packetery\Checkout\Model\Carrier\AbstractCarrier;
use Packetery\Checkout\Model\Carrier\AbstractDynamicCarrier;
use Packetery\Checkout\Model\Carrier\Facade;
use Packetery\Checkout\Model\Carrier\Imp\Packetery\Config;
use Packetery\Checkout\Model\OrderCurrencyResolver;
use Packetery\Checkout\Model\Packet;
use Packetery\Checkout\Model\PacketRepository;
use Packetery\Checkout\Model\ResourceModel\Order\Collection;
use Packetery\Checkout\Test\BaseTest;
use Packetery\Checkout\Ui\Order\DataProvider;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider as DataProviderAttribute;

/**
 * Relevance/prefill rules live in private DataProvider methods, driven through reflection
 */
#[AllowMockObjectsWithoutExpectations]
class DataProviderTest extends BaseTest
{
    private const ORDER_NUMBER = '000000004';
    private const ORDER_ITEM_ID = 7;
    private const DEFAULT_BOX_ID = 9;

    /**
     * COD field shows only when cod > 0 and the carrier allows cash on delivery (cod === 0.0 is not COD)
     *
     * @throws \ReflectionException
     */
    #[DataProviderAttribute('codEligibilityProvider')]
    public function testIsCodEligible(?float $cod, ?string $carrierMode, bool $expected): void
    {
        $dynamicCarrier = null;
        if ($carrierMode !== null) {
            $dynamicCarrier = $this->createStub(AbstractDynamicCarrier::class);
            $dynamicCarrier->method('disallowsCod')
                ->willReturn($carrierMode === 'blocks');
        }

        $provider = $this->createProxy(DataProvider::class);

        $this->assertSame(
            $expected,
            $this->invokeMethod($provider, 'isCodEligible', [$cod, $dynamicCarrier])
        );
    }

    /**
     * @return array<string, array{?float, ?string, bool}>
     */
    public static function codEligibilityProvider(): array
    {
        return [
            'null cod is not a COD order' => [null, null, false],
            'zero cod is not a COD order' => [0.0, null, false],
            'positive cod, no dynamic carrier (own Packeta allows COD)' => [100.0, null, true],
            'positive cod, carrier allows COD' => [100.0, 'allows', true],
            'positive cod, carrier blocks COD' => [100.0, 'blocks', false],
        ];
    }

    /**
     * Store-default weight prefill (display only): only when the order has no positive weight,
     * only when a positive store default exists, and as a string to match the decimal column
     *
     * @throws \ReflectionException
     */
    #[DataProviderAttribute('defaultWeightPrefillProvider')]
    public function testResolveDefaultWeightPrefill(
        ?float $storedWeight,
        ?float $configDefault,
        bool $hasConfig,
        ?string $expected
    ): void {
        $config = null;
        if ($hasConfig) {
            $config = $this->createStub(Config::class);
            $config->method('getDefaultWeight')
                ->willReturn($configDefault);
        }

        $provider = $this->createProxy(DataProvider::class);

        $this->assertSame(
            $expected,
            $this->invokeMethod($provider, 'resolveDefaultWeightPrefill', [$storedWeight, $config])
        );
    }

    /**
     * @return array<string, array{?float, ?float, bool, ?string}>
     */
    public static function defaultWeightPrefillProvider(): array
    {
        return [
            'stored positive weight takes precedence over default' => [
                2.5,
                5.0,
                true,
                null,
            ],
            'zero stored weight falls back to default' => [
                0.0,
                5.0,
                true,
                '5',
            ],
            'null stored weight falls back to default' => [
                null,
                5.0,
                true,
                '5',
            ],
            'decimal default kept as string' => [
                null,
                2.5,
                true,
                '2.5',
            ],
            'zero default does not prefill' => [
                null,
                0.0,
                true,
                null,
            ],
            'null default does not prefill' => [
                null,
                null,
                true,
                null,
            ],
            'no packetery config does not prefill' => [
                0.0,
                null,
                false,
                null,
            ],
        ];
    }

    /**
     * Store-default box prefill (display only): only when none is chosen and a default box exists;
     * a missing default (NoSuchEntityException) resolves to null
     *
     * @throws \ReflectionException
     */
    #[DataProviderAttribute('defaultBoxPrefillProvider')]
    public function testResolveDefaultBoxPrefill(
        ?int $currentBoxId,
        string $defaultBoxMode,
        ?int $defaultBoxId,
        ?int $expected
    ): void {
        $boxRepository = $this->createStub(BoxRepository::class);

        if ($defaultBoxMode === 'throw') {
            $boxRepository->method('getDefaultBox')
                ->willThrowException(new NoSuchEntityException());
        } elseif ($defaultBoxMode === 'box') {
            $boxRepository->method('getDefaultBox')
                ->willReturn($this->prepareBoxStub($defaultBoxId));
        } else {
            $boxRepository->method('getDefaultBox')
                ->willReturn(null);
        }

        $provider = $this->createProxy(DataProvider::class, ['boxRepository' => $boxRepository]);

        $this->assertSame(
            $expected,
            $this->invokeMethod($provider, 'resolveDefaultBoxPrefill', [$currentBoxId])
        );
    }

    /**
     * @return array<string, array{?int, string, ?int, ?int}>
     */
    public static function defaultBoxPrefillProvider(): array
    {
        return [
            'box already set, no prefill' => [
                5,
                'box',
                3,
                null,
            ],
            'zero box id treated as unset' => [
                0,
                'box',
                3,
                3,
            ],
            'no box set, default exists' => [
                null,
                'box',
                3,
                3,
            ],
            'no box set, no default' => [
                null,
                'null',
                null,
                null,
            ],
            'no box set, lookup throws' => [
                null,
                'throw',
                null,
                null,
            ],
        ];
    }

    /**
     * The Delivery section shows only when it has a picker to offer: a pickup point (any country)
     * or an address (HD into a CZ/SK country with address validation); otherwise the heading is hidden
     *
     * @throws \ReflectionException
     */
    #[DataProviderAttribute('deliverySectionVisibilityProvider')]
    public function testIsDeliverySectionVisible(
        ?string $shippingMethod,
        ?string $countryId,
        bool $hasAddress,
        bool $expected
    ): void {
        $order = $this->prepareOrderMock($shippingMethod, $hasAddress ? $countryId : null);

        $provider = $this->createProxy(DataProvider::class);

        $this->assertSame(
            $expected,
            $this->invokeMethod($provider, 'isDeliverySectionVisible', [$order])
        );
    }

    /**
     * @return array<string, array{?string, ?string, bool, bool}>
     */
    public static function deliverySectionVisibilityProvider(): array
    {
        return [
            'pickup point CZ visible' => [
                self::SHIPPING_PICKUP_POINT,
                'CZ',
                true,
                true,
            ],
            'pickup point any country visible' => [
                self::SHIPPING_PICKUP_POINT,
                'PL',
                true,
                true,
            ],
            'HD into CZ (address validation) visible' => [
                self::SHIPPING_ADDRESS_DELIVERY,
                'CZ',
                true,
                true,
            ],
            'HD into PL (no address validation) hidden' => [
                self::SHIPPING_ADDRESS_DELIVERY,
                'PL',
                true,
                false,
            ],
            'non-packetery method hidden' => [
                self::SHIPPING_NON_PACKETERY,
                'CZ',
                true,
                false,
            ],
            'pickup point without address hidden' => [
                self::SHIPPING_PICKUP_POINT,
                null,
                false,
                false,
            ],
            'empty shipping method hidden' => [
                '',
                'CZ',
                true,
                false,
            ],
            'null shipping method hidden' => [
                null,
                'CZ',
                true,
                false,
            ],
        ];
    }

    /**
     * getMeta appends the order currency (the static form XML can't carry it) as the value/cod field
     * suffix. Driven through the real order resolution, so a resolveOrderItem/resolveMagentoOrder
     * regression that drops the order also drops the suffix.
     *
     * @throws \ReflectionException
     */
    #[DataProviderAttribute('currencySuffixProvider')]
    public function testGetMetaAddsCurrencySuffix(?string $currency): void
    {
        $magentoOrder = $this->prepareOrderMock(self::SHIPPING_PICKUP_POINT, 'CZ');
        $magentoOrder->method('getIncrementId')
            ->willReturn('000000004');

        $packeteryOrder = $this->createStub(\Packetery\Checkout\Model\Order::class);
        $packeteryOrder->method('getOrderNumber')
            ->willReturn('000000004');

        $collection = $this->createStub(Collection::class);
        $collection->method('getItems')
            ->willReturn([$packeteryOrder]);

        $request = $this->createStub(RequestInterface::class);
        $request->method('getParam')
            ->willReturn('5');

        $orderRepository = $this->createStub(OrderRepositoryInterface::class);
        $orderRepository->method('get')
            ->willReturn($magentoOrder);

        $resolver = $this->createStub(OrderCurrencyResolver::class);
        $resolver->method('resolve')
            ->willReturnCallback(
                static fn (?object $item, ?object $order): ?string =>
                    $item !== null && $order !== null ? $currency : null
            );

        $provider = $this->createProxy(
            DataProvider::class,
            [
                'collection' => $collection,
                'request' => $request,
                'orderRepository' => $orderRepository,
                'orderCurrencyResolver' => $resolver,
                'meta' => [],
            ]
        );

        $meta = $provider->getMeta();
        $packetChildren = $meta['general']['children']['packet']['children'] ?? null;

        if ($currency === null) {
            $this->assertNull($packetChildren);

            return;
        }

        $this->assertSame($currency, $packetChildren['value']['arguments']['data']['config']['addafter']);
        $this->assertSame($currency, $packetChildren['cod']['arguments']['data']['config']['addafter']);
    }

    /**
     * @return array<string, array{?string}>
     */
    public static function currencySuffixProvider(): array
    {
        return [
            'CZK suffix on value and cod' => ['CZK'],
            'EUR suffix on value and cod' => ['EUR'],
            'no currency, no suffix' => [null],
        ];
    }

    /**
     * The isolated tests cover the rules; this drives getData end to end, so a misordered guard
     * or a wrong argument wiring the carrier into isCodEligible and the prefills is caught too
     *
     * @throws \ReflectionException
     */
    public function testGetDataAssemblesMiscFlagsAndPrefills(): void
    {
        $provider = $this->makeSingleOrderProvider(
            [
                'cod' => 150.0,
                'defaultWeight' => 2.5,
                'requiresSize' => true,
                'defaultBoxId' => self::DEFAULT_BOX_ID,
            ]
        );

        $general = $provider->getData()[self::ORDER_ITEM_ID]['general'];

        $this->assertNull($general['consign_password']);
        $this->assertSame('0', $general['misc']['showConsignPassword']);
        $this->assertSame('2.5', $general['weight']);
        $this->assertSame(self::DEFAULT_BOX_ID, $general['box_id']);
        $this->assertSame('1', $general['misc']['isCodEligible']);
        $this->assertSame('1', $general['misc']['isSizeEligible']);
        $this->assertSame('0', $general['misc']['isPickupPointDelivery']);
        $this->assertSame('[]', $general['misc']['widgetVendors']);
    }

    /**
     * The box prefill is wired behind two getData guards the isolated test can't see: it runs only
     * when the carrier requires a size and no packet has been submitted yet
     *
     * @throws \ReflectionException
     */
    #[DataProviderAttribute('boxPrefillProvider')]
    public function testGetDataPrefillsBoxOnlyWhenEligible(
        bool $requiresSize,
        bool $hasPacket,
        ?int $currentBoxId,
        bool $expectPrefill
    ): void {
        $provider = $this->makeSingleOrderProvider(
            [
                'weight' => 1.0,
                'currentBoxId' => $currentBoxId,
                'hasPacket' => $hasPacket,
                'requiresSize' => $requiresSize,
                'defaultBoxId' => self::DEFAULT_BOX_ID,
            ]
        );

        $general = $provider->getData()[self::ORDER_ITEM_ID]['general'];

        if ($expectPrefill) {
            $this->assertSame(self::DEFAULT_BOX_ID, $general['box_id']);

            return;
        }

        $this->assertArrayNotHasKey('box_id', $general);
    }

    /**
     * @return array<string, array{bool, bool, ?int, bool}>
     */
    public static function boxPrefillProvider(): array
    {
        return [
            'size required, not submitted, no box → prefilled' => [true, false, null, true],
            'size required but already submitted → no prefill' => [true, true, null, false],
            'size not required → no prefill' => [false, false, null, false],
            'size required but box already set → no prefill' => [true, false, 4, false],
        ];
    }

    /**
     * Builds a DataProvider over one packetery_order row wired through the whole getData chain
     * (Magento order, per-store carrier config, dynamic carrier via the facade brain). Address delivery
     * keeps the pickup-point widget-vendor branch out of the way.
     *
     * @param array{
     *     weight?: ?float,
     *     cod?: ?float,
     *     currentBoxId?: ?int,
     *     hasPacket?: bool,
     *     defaultWeight?: ?float,
     *     disallowsCod?: bool,
     *     requiresSize?: bool,
     *     defaultBoxId?: ?int
     * } $scenario
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws \ReflectionException
     */
    private function makeSingleOrderProvider(array $scenario): DataProvider
    {
        $weight = $scenario['weight'] ?? null;
        $cod = $scenario['cod'] ?? null;
        $currentBoxId = $scenario['currentBoxId'] ?? null;
        $hasPacket = $scenario['hasPacket'] ?? false;
        $defaultWeight = $scenario['defaultWeight'] ?? null;
        $disallowsCod = $scenario['disallowsCod'] ?? false;
        $requiresSize = $scenario['requiresSize'] ?? false;
        $defaultBoxId = $scenario['defaultBoxId'] ?? null;

        $item = $this->createStub(\Packetery\Checkout\Model\Order::class);
        $item->method('getId')
            ->willReturn(self::ORDER_ITEM_ID);
        $item->method('getData')
            ->willReturn(['order_number' => self::ORDER_NUMBER]);
        $item->method('getWeight')
            ->willReturn($weight);
        $item->method('getCod')
            ->willReturn($cod);
        $item->method('getBoxId')
            ->willReturn($currentBoxId);
        $item->method('isCarrier')
            ->willReturn(false);

        $collection = $this->createStub(Collection::class);
        $collection->method('getItems')
            ->willReturn([$item]);

        $packetRepository = $this->createStub(PacketRepository::class);
        $packetRepository->method('findLatestByOrderNumber')
            ->willReturn($hasPacket ? $this->createStub(Packet::class) : null);

        $magentoOrder = $this->prepareOrderMock(self::SHIPPING_ADDRESS_DELIVERY, 'CZ');
        $magentoOrder->method('getIncrementId')
            ->willReturn(self::ORDER_NUMBER);
        $magentoOrder->method('loadByIncrementId')
            ->willReturnSelf();

        $orderFactory = $this->createStub(OrderFactory::class);
        $orderFactory->method('create')
            ->willReturn($magentoOrder);

        $config = $this->createStub(Config::class);
        $config->method('isShowConsignPassword')
            ->willReturn(false);
        $config->method('getDefaultWeight')
            ->willReturn($defaultWeight);

        $dynamicCarrier = $this->createStub(AbstractDynamicCarrier::class);
        $dynamicCarrier->method('disallowsCod')
            ->willReturn($disallowsCod);
        $dynamicCarrier->method('requiresSize')
            ->willReturn($requiresSize);

        $brain = $this->createStub(AbstractBrain::class);
        $brain->method('getDynamicCarrierById')
            ->willReturn($dynamicCarrier);

        $carrier = $this->createStub(AbstractCarrier::class);
        $carrier->method('getPacketeryBrain')
            ->willReturn($brain);

        $facade = $this->createStub(Facade::class);
        $facade->method('getPacketeryCarrierConfig')
            ->willReturn($config);
        $facade->method('getMagentoCarrier')
            ->willReturn($carrier);

        $boxRepository = $this->createStub(BoxRepository::class);
        $boxRepository->method('getDefaultBox')
            ->willReturn($defaultBoxId !== null ? $this->prepareBoxStub($defaultBoxId) : null);

        return $this->createProxy(
            DataProvider::class,
            [
                'collection' => $collection,
                'packetRepository' => $packetRepository,
                'orderFactory' => $orderFactory,
                'carrierFacade' => $facade,
                'boxRepository' => $boxRepository,
            ]
        );
    }
}
