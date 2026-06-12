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
use Packetery\Checkout\Model\PacketFactory;
use Packetery\Checkout\Model\ResourceModel\Order as OrderResource;
use Packetery\Checkout\Model\ResourceModel\Packet as PacketResource;
use Packetery\Checkout\Model\ResourceModel\Packet\Collection as PacketCollection;
use Packetery\Checkout\Model\ResourceModel\Packet\CollectionFactory as PacketCollectionFactory;
use Packetery\Checkout\Model\Weight\Calculator;
use Packetery\Checkout\Test\BaseTest;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;

#[AllowMockObjectsWithoutExpectations]
class PacketSubmitterTest extends BaseTest
{
    /**
     * @throws \ReflectionException
     */
    public function testResolveWeightPrefersManualWeight(): void
    {
        $this->assertSame(3.5, $this->invokeResolveWeight(3.5, 1.2, 2.0));
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
        $soapApiClient->method('createPacket')->willReturn(new CreatePacketResult('PKT-1'));

        $submitter = $this->makeSubmitter($soapApiClient, $orderResource);

        $submitter->submitPacket($packeteryOrder, $this->makeMagentoOrder());

        // setWeight is a DataObject magic setter, so the write-back is read back off the raw data
        $this->assertSame(3.5, $packeteryOrder->getData('weight'));
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

        $packeteryOrder = $this->makePacketeryOrder(3.5);
        $packeteryOrder->expects($this->never())
            ->method('markExported');

        $soapApiClient = $this->createMock(SoapApiClient::class);
        $soapApiClient->method('createPacket')
            ->willThrowException(new PacketSubmissionException('rejected'));

        $submitter = $this->makeSubmitter($soapApiClient, $orderResource);

        $this->assertException(
            PacketSubmissionException::class,
            function () use ($submitter, $packeteryOrder): void {
                $submitter->submitPacket($packeteryOrder, $this->makeMagentoOrder());
            }
        );

        // nothing persisted on failure: the weight write-back never ran
        $this->assertNull($packeteryOrder->getData('weight'));
    }

    /**
     * No manual weight, no product weight and no configured default:
     * the resolver does not crash, it stays at 0.0
     * (see testResolveWeightStaysZeroWhenNothingResolves) and that zero is what reaches the API,
     * which rejects the packet, so nothing is persisted.
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
                $submitter->submitPacket($packeteryOrder, $this->makeMagentoOrder());
            }
        );

        $this->assertSame(0.0, $sentWeight);
        $this->assertNull($packeteryOrder->getData('weight'));
    }

    /**
     * A box with all dimensions set sends the size to the API in whole mm (cm × 10),
     * mapping depth to the API length axis.
     *
     * @throws \ReflectionException
     */
    public function testSubmitPacketSendsBoxSizeInMillimetres(): void
    {
        $box = $this->makeBox(30.0, 20.0, 10.0);

        $sentSize = null;
        $soapApiClient = $this->createMock(SoapApiClient::class);
        $soapApiClient->method('createPacket')
            ->willReturnCallback(
                function (string $apiPassword, PacketAttributes $attributes) use (&$sentSize): CreatePacketResult {
                    $sentSize = $attributes->toArray()['size'] ?? null;

                    return new CreatePacketResult('PKT-1');
                }
            );

        $submitter = $this->makeSubmitter($soapApiClient, $this->createMock(OrderResource::class), $box);

        $submitter->submitPacket($this->makePacketeryOrder(3.5, 7), $this->makeMagentoOrder());

        $this->assertSame(['length' => 300, 'width' => 200, 'height' => 100], $sentSize);
    }

    /**
     * A box dimension is null only on a row edited directly in the database, so the size is skipped
     * instead of crashing the strict conversion: the packet still submits, just without size.
     *
     * @throws \ReflectionException
     */
    public function testSubmitPacketSkipsSizeWhenBoxDimensionMissing(): void
    {
        $box = $this->makeBox(null, 20.0, 10.0);

        $sizeKeyExists = true;
        $soapApiClient = $this->createMock(SoapApiClient::class);
        $soapApiClient->method('createPacket')
            ->willReturnCallback(
                function (string $apiPassword, PacketAttributes $attributes) use (&$sizeKeyExists): CreatePacketResult {
                    $sizeKeyExists = array_key_exists('size', $attributes->toArray());

                    return new CreatePacketResult('PKT-1');
                }
            );

        $submitter = $this->makeSubmitter($soapApiClient, $this->createMock(OrderResource::class), $box);

        $submitter->submitPacket($this->makePacketeryOrder(3.5, 7), $this->makeMagentoOrder());

        $this->assertFalse($sizeKeyExists);
    }

    /**
     * Order with a manual weight so `resolveWeight` short-circuits, on a pickup-point method with no
     * carrier pickup point, no box, no COD and no adult content, so `submitPacket` takes its shortest path.
     * Partial mock: the declared getters are stubbed, but the magic setWeight()/getData() stay real
     * so the test can observe the write-back.
     */
    private function makePacketeryOrder(?float $manualWeight, ?int $boxId = null): MockObject
    {
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
                'markExported',
            ]
        );
        $packeteryOrder->method('getOrderNumber')->willReturn('000000004');
        $packeteryOrder->method('getWeight')->willReturn($manualWeight);
        $packeteryOrder->method('getValue')->willReturn(100.0);
        $packeteryOrder->method('getCod')->willReturn(0.0);
        $packeteryOrder->method('getRecipientFirstname')->willReturn('John');
        $packeteryOrder->method('getRecipientLastname')->willReturn('Smith');
        $packeteryOrder->method('getRecipientCompany')->willReturn('');
        $packeteryOrder->method('getRecipientEmail')->willReturn('john@example.com');
        $packeteryOrder->method('getRecipientPhone')->willReturn('+420123456789');
        $packeteryOrder->method('getPointId')->willReturn(123);
        $packeteryOrder->method('getCarrierPickupPoint')->willReturn(null);
        $packeteryOrder->method('getBoxId')->willReturn($boxId);
        $packeteryOrder->method('getAdultContent')->willReturn(false);

        return $packeteryOrder;
    }

    private function makeMagentoOrder(): MockObject
    {
        $magentoOrder = $this->createMock(\Magento\Sales\Model\Order::class);
        $magentoOrder->method('getStoreId')->willReturn(1);
        $magentoOrder->method('getShippingMethod')->willReturn('packetery_pickupPointDelivery');
        $magentoOrder->method('getShippingAddress')->willReturn(null);

        return $magentoOrder;
    }

    private function makeSubmitter(MockObject $soapApiClient, MockObject $orderResource, ?\Packetery\Checkout\Model\Box $box = null): PacketSubmitter
    {
        $scopeConfig = $this->createStub(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturn('configured');

        $packetCollection = $this->createStub(PacketCollection::class);
        $packetCollection->method('getSize')->willReturn(0);
        $packetCollectionFactory = $this->createStub(PacketCollectionFactory::class);
        $packetCollectionFactory->method('create')->willReturn($packetCollection);

        $packetFactory = $this->createStub(PacketFactory::class);
        $packetFactory->method('create')->willReturn($this->createStub(\Packetery\Checkout\Model\Packet::class));

        $config = $this->createStub(Config::class);
        $config->method('isShowConsignPassword')->willReturn(false);
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
                'scopeConfig' => $scopeConfig,
                'weightCalculator' => $this->createStub(Calculator::class),
                'packetFactory' => $packetFactory,
                'packetCollectionFactory' => $packetCollectionFactory,
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
     * A pickup-point order (see makeMagentoOrder) carrying a box, so submitPacket resolves the box and
     * builds the size from its dimensions; a null dimension models a row edited directly in the database.
     */
    private function makeBox(?float $depth, ?float $width, ?float $height): \Packetery\Checkout\Model\Box
    {
        $box = $this->createStub(\Packetery\Checkout\Model\Box::class);
        $box->method('getName')->willReturn('M');
        $box->method('getDepth')->willReturn($depth);
        $box->method('getWidth')->willReturn($width);
        $box->method('getHeight')->willReturn($height);

        return $box;
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
