<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit\Model\Packet;

use Packetery\Checkout\Model\Api\PacketSubmissionException;
use Packetery\Checkout\Model\Api\Result\CreatePacketResult;
use Packetery\Checkout\Model\Api\SoapApiClient;
use Packetery\Checkout\Model\Carrier\Facade;
use Packetery\Checkout\Model\Carrier\Imp\Packetery\Config;
use Packetery\Checkout\Model\OrderCurrencyResolver;
use Packetery\Checkout\Model\Packet\PacketAttributes;
use Packetery\Checkout\Model\Packet\PacketSubmitter;
use Packetery\Checkout\Model\Packet\SubmitPreconditions;
use Packetery\Checkout\Model\PacketFactory;
use Packetery\Checkout\Model\ResourceModel\Order as OrderResource;
use Packetery\Checkout\Model\ResourceModel\Packet as PacketResource;
use Packetery\Checkout\Model\Weight\Calculator;
use Packetery\Checkout\Test\BaseTest;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

#[AllowMockObjectsWithoutExpectations]
class PacketSubmitterTest extends BaseTest
{
    private const DEFAULT_WEIGHT = 3.5;
    private const PACKET_NUMBER = 'PKT-1';
    private const CONSIGN_PASSWORD = 'A1B2C3';

    /**
     * @throws \ReflectionException
     */
    public function testResolveWeightPrefersManualWeight(): void
    {
        $this->assertSame(self::DEFAULT_WEIGHT, $this->invokeResolveWeight(self::DEFAULT_WEIGHT, 1.2, 2.0));
    }

    /**
     * @throws \ReflectionException
     */
    public function testResolveWeightFallsBackToProductWeight(): void
    {
        $this->assertSame(1.2, $this->invokeResolveWeight(null, 1.2, 2.0));
    }

    /**
     * No saved manual weight and no product weight, use the per-store configured default.
     *
     * @throws \ReflectionException
     */
    public function testResolveWeightFallsBackToConfiguredDefault(): void
    {
        $this->assertSame(2.0, $this->invokeResolveWeight(null, 0.0, 2.0));
    }

    /**
     * @throws \ReflectionException
     */
    public function testResolveWeightStaysZeroWhenNothingResolves(): void
    {
        $this->assertSame(0.0, $this->invokeResolveWeight(null, 0.0, null));
    }

    /**
     * After a successful submit the resolved weight is written back to the order so a record that
     * fell back to the product or configured default no longer keeps an empty weight.
     *
     * @throws \ReflectionException
     */
    public function testSubmitPacketWritesResolvedWeightBackAndMarksExported(): void
    {
        $orderResource = $this->createMock(OrderResource::class);
        $orderResource->expects($this->once())->method('save');

        $packeteryOrder = $this->makePacketeryOrder(3.5);
        $packeteryOrder->expects($this->once())->method('markExported');

        $soapApiClient = $this->createMock(SoapApiClient::class);
        $soapApiClient->method('createPacket')->willReturn(new CreatePacketResult(self::PACKET_NUMBER));

        $submitter = $this->makeSubmitter($soapApiClient, $orderResource);

        $submitter->submitPacket($packeteryOrder, $this->prepareOrderMock(self::SHIPPING_PICKUP_POINT));

        // setWeight is a DataObject magic setter, so the write-back is read back off the raw data
        $this->assertSame(self::DEFAULT_WEIGHT, $packeteryOrder->getData('weight'));
    }

    /**
     * When `createPacket` fails nothing is persisted: no weight write-back and no order save.
     *
     * @throws \ReflectionException
     */
    public function testSubmitPacketSavesNothingWhenCreatePacketFails(): void
    {
        $orderResource = $this->createMock(OrderResource::class);
        $orderResource->expects($this->never())
            ->method('save');

        $packeteryOrder = $this->makePacketeryOrder(self::DEFAULT_WEIGHT);
        $packeteryOrder->expects($this->never())
            ->method('markExported');

        $soapApiClient = $this->createMock(SoapApiClient::class);
        $soapApiClient->method('createPacket')
            ->willThrowException(new PacketSubmissionException('rejected'));

        $submitter = $this->makeSubmitter($soapApiClient, $orderResource);

        $this->assertException(
            PacketSubmissionException::class,
            function () use ($submitter, $packeteryOrder): void {
                $submitter->submitPacket($packeteryOrder, $this->prepareOrderMock(self::SHIPPING_PICKUP_POINT));
            }
        );

        // nothing persisted on failure: the weight write-back never ran
        $this->assertNull($packeteryOrder->getData('weight'));
    }

    /**
     * No manual, product, or configured weight: the resolver stays at 0.0; that zero reaches the API,
     * which rejects the packet, so nothing is persisted
     *
     * @throws \ReflectionException
     */
    public function testSubmitsZeroWeightWhenAllWeightsMissing(): void
    {
        $orderResource = $this->createMock(OrderResource::class);
        $orderResource->expects($this->never())
            ->method('save');

        $packeteryOrder = $this->makePacketeryOrder(null);
        $packeteryOrder->expects($this->never())
            ->method('markExported');

        $sentWeight = null;
        $soapApiClient = $this->createMock(SoapApiClient::class);
        $soapApiClient->method('createPacket')
            ->willReturnCallback(
                function (string $apiPassword, PacketAttributes $attributes) use (&$sentWeight): CreatePacketResult {
                    $sentWeight = $attributes->toArray()['weight'];

                    // the real API rejects a zero-weight packet
                    throw new PacketSubmissionException('weight: Weight is required');
                }
            );

        $submitter = $this->makeSubmitter($soapApiClient, $orderResource);

        $this->assertException(
            PacketSubmissionException::class,
            function () use ($submitter, $packeteryOrder): void {
                $submitter->submitPacket($packeteryOrder, $this->prepareOrderMock(self::SHIPPING_PICKUP_POINT));
            }
        );

        $this->assertSame(0.0, $sentWeight);
        $this->assertNull($packeteryOrder->getData('weight'));
    }

    /**
     * @return array<string, array{?float, ?float, ?float, ?array<string, int>}>
     */
    public static function boxSizeProvider(): array
    {
        return [
            'all dimensions → mm (depth maps to API length)' => [
                30.0,
                20.0,
                10.0,
                ['length' => 300, 'width' => 200, 'height' => 100],
            ],
            'null dimension (DB-edited row) → size skipped' => [
                null,
                20.0,
                10.0,
                null,
            ],
        ];
    }

    /**
     * @param ?array<string, int> $expectedSize
     * @throws \ReflectionException
     */
    #[DataProvider('boxSizeProvider')]
    public function testSubmitBoxSize(
        ?float $depth,
        ?float $width,
        ?float $height,
        ?array $expectedSize
    ): void {
        $box = $this->prepareBoxStub(
            1,
            'M',
            $depth,
            $width,
            $height
        );

        $captured = [];
        $soapApiClient = $this->createMock(SoapApiClient::class);
        $soapApiClient->method('createPacket')
            ->willReturnCallback(
                function (string $apiPassword, PacketAttributes $attributes) use (&$captured): CreatePacketResult {
                    $captured = $attributes->toArray();

                    return new CreatePacketResult(self::PACKET_NUMBER);
                }
            );
        $submitter = $this->makeSubmitter($soapApiClient, $this->createMock(OrderResource::class), $box);

        $submitter->submitPacket(
            $this->makePacketeryOrder(self::DEFAULT_WEIGHT, 7),
            $this->prepareOrderMock(self::SHIPPING_PICKUP_POINT)
        );

        if ($expectedSize === null) {
            $this->assertArrayNotHasKey('size', $captured);

            return;
        }

        $this->assertSame($expectedSize, $captured['size']);
    }

    /** @return array<string, array{float, ?float}> */
    public static function codProvider(): array
    {
        return [
            'zero COD omits attribute' => [0.0, null],
            'positive COD is sent' => [150.0, 150.0],
        ];
    }

    /**
     * @throws \ReflectionException
     */
    #[DataProvider('codProvider')]
    public function testSubmitCodAttribute(float $cod, ?float $expectedCod): void
    {
        $captured = [];
        $soapApiClient = $this->createMock(SoapApiClient::class);
        $soapApiClient->method('createPacket')
            ->willReturnCallback(
                function (string $apiPassword, PacketAttributes $attributes) use (&$captured): CreatePacketResult {
                    $captured = $attributes->toArray();

                    return new CreatePacketResult(self::PACKET_NUMBER);
                }
            );
        $submitter = $this->makeSubmitter($soapApiClient, $this->createMock(OrderResource::class));

        $submitter->submitPacket(
            $this->makePacketeryOrder(
                self::DEFAULT_WEIGHT,
                null,
                100.0,
                $cod
            ),
            $this->prepareOrderMock(self::SHIPPING_PICKUP_POINT)
        );

        $this->assertSame($expectedCod, $captured['cod'] ?? null);
    }

    /** @return array<string, array{bool, bool, ?bool}> */
    public static function adultContentProvider(): array
    {
        return [
            'flag on + own pickup + base country → sent' => [true, false, true],
            'flag on + carrier pickup → omitted' => [true, true, null],
            'flag off → omitted' => [false, false, null],
        ];
    }

    /**
     * @throws \ReflectionException
     */
    #[DataProvider('adultContentProvider')]
    public function testSubmitAdultContentAttribute(
        bool $adultContent,
        bool $isCarrier,
        ?bool $expectedAdultContent
    ): void {
        $captured = [];
        $soapApiClient = $this->createMock(SoapApiClient::class);
        $soapApiClient->method('createPacket')
            ->willReturnCallback(
                function (string $apiPassword, PacketAttributes $attributes) use (&$captured): CreatePacketResult {
                    $captured = $attributes->toArray();

                    return new CreatePacketResult(self::PACKET_NUMBER);
                }
            );

        $submitter = $this->makeSubmitter($soapApiClient, $this->createMock(OrderResource::class));
        $submitter->submitPacket(
            $this->makePacketeryOrder(
                self::DEFAULT_WEIGHT,
                null,
                100.0,
                0.0,
                $adultContent,
                $isCarrier
            ),
            $this->prepareOrderMock(self::SHIPPING_PICKUP_POINT, 'CZ')
        );

        $this->assertSame($expectedAdultContent, $captured['adultContent'] ?? null);
    }

    /**
     * Box is snapshotted onto the packet so the detail stays correct if the box is later changed
     *
     * @throws \ReflectionException
     */
    public function testSubmitSnapshotsBoxDimensionsOnSuccess(): void
    {
        $box = $this->prepareBoxStub(
            1,
            'M',
            30.0,
            20.0,
            10.0
        );

        $packet = $this->createMock(\Packetery\Checkout\Model\Packet::class);
        $packet->expects($this->once())
            ->method('setBoxName')
            ->with('M');
        $packet->expects($this->once())
            ->method('setBoxDepth')
            ->with(30.0);
        $packet->expects($this->once())
            ->method('setBoxWidth')
            ->with(20.0);
        $packet->expects($this->once())
            ->method('setBoxHeight')
            ->with(10.0);

        $soapApiClient = $this->createMock(SoapApiClient::class);
        $soapApiClient->method('createPacket')
            ->willReturn(new CreatePacketResult(self::PACKET_NUMBER));

        $submitter = $this->makeSubmitter($soapApiClient, $this->createMock(OrderResource::class), $box, $packet);

        $submitter->submitPacket(
            $this->makePacketeryOrder(self::DEFAULT_WEIGHT, 7),
            $this->prepareOrderMock(self::SHIPPING_PICKUP_POINT)
        );
    }

    /**
     * With the consignment code enabled the submit fetches it through packetInfo and stores it on the packet
     *
     * @throws \ReflectionException
     */
    public function testSubmitFetchesConsignPasswordWhenEnabled(): void
    {
        $packetInfo = $this->createStub(\Packetery\Checkout\Model\Api\Result\PacketInfoResult::class);
        $packetInfo->method('getConsignPassword')
            ->willReturn(self::CONSIGN_PASSWORD);

        $soapApiClient = $this->createMock(SoapApiClient::class);
        $soapApiClient->method('createPacket')
            ->willReturn(new CreatePacketResult(self::PACKET_NUMBER));
        $soapApiClient->expects($this->once())
            ->method('packetInfo')
            ->willReturn($packetInfo);

        $packet = $this->createMock(\Packetery\Checkout\Model\Packet::class);
        $packet->expects($this->once())
            ->method('setConsignPassword')
            ->with(self::CONSIGN_PASSWORD);

        $submitter = $this->makeSubmitter(
            $soapApiClient,
            $this->createMock(OrderResource::class),
            null,
            $packet,
            true
        );

        $submitter->submitPacket(
            $this->makePacketeryOrder(self::DEFAULT_WEIGHT),
            $this->prepareOrderMock(self::SHIPPING_PICKUP_POINT)
        );
    }

    /**
     * With the consignment code disabled the submit never calls packetInfo and stores null
     *
     * @throws \ReflectionException
     */
    public function testSubmitSkipsConsignPasswordWhenDisabled(): void
    {
        $soapApiClient = $this->createMock(SoapApiClient::class);
        $soapApiClient->method('createPacket')
            ->willReturn(new CreatePacketResult(self::PACKET_NUMBER));
        $soapApiClient->expects($this->never())
            ->method('packetInfo');

        $packet = $this->createMock(\Packetery\Checkout\Model\Packet::class);
        $packet->expects($this->once())
            ->method('setConsignPassword')
            ->with(null);

        $submitter = $this->makeSubmitter(
            $soapApiClient,
            $this->createMock(OrderResource::class),
            packet: $packet
        );

        $submitter->submitPacket(
            $this->makePacketeryOrder(self::DEFAULT_WEIGHT),
            $this->prepareOrderMock(self::SHIPPING_PICKUP_POINT)
        );
    }

    /**
     * Partial mock: declared getters are stubbed, magic setWeight()/getData() stay real so tests see the write-back
     */
    private function makePacketeryOrder(
        ?float $manualWeight,
        ?int $boxId = null,
        ?float $value = 100.0,
        ?float $cod = 0.0,
        bool $adultContent = false,
        bool $isCarrier = false
    ): \Packetery\Checkout\Model\Order&MockObject {
        $packeteryOrder = $this->createPartialMock(
            \Packetery\Checkout\Model\Order::class,
            [
                'getOrderNumber',
                'getWeight',
                'getValue',
                'getCod',
                'getRecipientFirstname',
                'getRecipientLastname',
                'getRecipientCompany',
                'getRecipientEmail',
                'getRecipientPhone',
                'getPointId',
                'getCarrierPickupPoint',
                'getBoxId',
                'getAdultContent',
                'isCarrier',
                'markExported',
            ]
        );
        $packeteryOrder->method('getOrderNumber')->willReturn('000000004');
        $packeteryOrder->method('getWeight')->willReturn($manualWeight);
        $packeteryOrder->method('getValue')->willReturn($value);
        $packeteryOrder->method('getCod')->willReturn($cod);
        $packeteryOrder->method('getRecipientFirstname')->willReturn('John');
        $packeteryOrder->method('getRecipientLastname')->willReturn('Smith');
        $packeteryOrder->method('getRecipientCompany')->willReturn('');
        $packeteryOrder->method('getRecipientEmail')->willReturn('john@example.com');
        $packeteryOrder->method('getRecipientPhone')->willReturn('+420123456789');
        $packeteryOrder->method('getPointId')->willReturn(123);
        $packeteryOrder->method('getCarrierPickupPoint')->willReturn(null);
        $packeteryOrder->method('getBoxId')->willReturn($boxId);
        $packeteryOrder->method('getAdultContent')->willReturn($adultContent);
        $packeteryOrder->method('isCarrier')->willReturn($isCarrier);

        return $packeteryOrder;
    }

    private function makeSubmitter(
        MockObject $soapApiClient,
        MockObject $orderResource,
        ?\Packetery\Checkout\Model\Box $box = null,
        ?object $packet = null,
        bool $showConsignPassword = false
    ): PacketSubmitter {
        $submitPreconditions = $this->createStub(SubmitPreconditions::class);
        $submitPreconditions->method('hasRequiredConfig')->willReturn(true);
        $submitPreconditions->method('isAlreadySubmitted')->willReturn(false);

        $packetFactory = $this->createStub(PacketFactory::class);
        $packetFactory->method('create')->willReturn($packet ?? $this->createStub(\Packetery\Checkout\Model\Packet::class));

        $config = $this->createStub(Config::class);
        $config->method('isShowConsignPassword')->willReturn($showConsignPassword);
        $config->method('getApiPassword')->willReturn('configured');
        $config->method('getSender')->willReturn('configured');
        $facade = $this->createStub(Facade::class);
        $facade->method('getPacketeryCarrierConfig')->willReturn($config);

        $currencyResolver = $this->createStub(OrderCurrencyResolver::class);
        $currencyResolver->method('resolve')->willReturn('CZK');

        $boxRepository = $this->createStub(\Packetery\Checkout\Model\BoxRepository::class);
        // getById() is only reached when a box id is set, and its return type is non-nullable Box,
        // so the stub is left unconfigured for the no-box tests
        if ($box !== null) {
            $boxRepository->method('getById')->willReturn($box);
        }

        return $this->createProxy(
            PacketSubmitter::class,
            [
                'soapApiClient' => $soapApiClient,
                'weightCalculator' => $this->createStub(Calculator::class),
                'packetFactory' => $packetFactory,
                'submitPreconditions' => $submitPreconditions,
                'packetResource' => $this->createStub(PacketResource::class),
                'orderResource' => $orderResource,
                'carrierFacade' => $facade,
                'logWriter' => $this->createStub(\Packetery\Checkout\Model\Log\LogWriter::class),
                'apiErrorFormatter' => $this->createStub(\Packetery\Checkout\Model\Log\ApiErrorFormatter::class),
                'boxRepository' => $boxRepository,
                'dimensionsConverter' => new \Packetery\Checkout\Model\Dimensions\Converter(),
                'orderCurrencyResolver' => $currencyResolver,
            ]
        );
    }

    /**
     * `resolveWeight` is private and `submitPacket` would need a live SOAP client, so reflection is used
     *
     * @throws \ReflectionException
     */
    private function invokeResolveWeight(?float $manualWeight, float $productWeight, ?float $defaultWeight): float
    {
        $weightCalculator = $this->createStub(Calculator::class);
        $weightCalculator->method('getOrderWeight')
            ->willReturn($productWeight);

        $config = $this->createStub(Config::class);
        $config->method('getDefaultWeight')
            ->willReturn($defaultWeight);

        $facade = $this->createStub(Facade::class);
        $facade->method('getPacketeryCarrierConfig')
            ->willReturn($config);

        $submitter = $this->createProxy(
            PacketSubmitter::class,
            [
                'weightCalculator' => $weightCalculator,
                'carrierFacade' => $facade,
            ]
        );

        $packeteryOrder = $this->createStub(\Packetery\Checkout\Model\Order::class);
        $packeteryOrder->method('getWeight')
            ->willReturn($manualWeight);

        $magentoOrder = $this->createStub(\Magento\Sales\Model\Order::class);
        $magentoOrder->method('getStoreId')
            ->willReturn(1);

        return (float) $this->invokeMethod(
            $submitter,
            'resolveWeight',
            [$packeteryOrder, $magentoOrder]
        );
    }
}
