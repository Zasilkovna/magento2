<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit\Model\Packet;

use Magento\Sales\Model\Order as MagentoOrder;
use Magento\Sales\Model\OrderFactory;
use Packetery\Checkout\Logger\BulkPacketSubmitLogger;
use Packetery\Checkout\Model\Api\PacketSubmissionException;
use Packetery\Checkout\Model\Log;
use Packetery\Checkout\Model\Log\LogWriter;
use Packetery\Checkout\Model\Order;
use Packetery\Checkout\Model\Packet\BulkPacketSubmitConsumer;
use Packetery\Checkout\Model\Packet\PacketSubmitLocalizedException;
use Packetery\Checkout\Model\Packet\PacketSubmitter;
use Packetery\Checkout\Model\ResourceModel\Order\Collection;
use Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory;
use Packetery\Checkout\Test\BaseTest;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class BulkPacketSubmitConsumerTest extends BaseTest
{
    private const ORDER_NUMBER = '100000123';

    /**
     * Pre-flight and unexpected failures are invisible in bulk (no flash message), so the consumer
     * records a per-shipment grid error to let the merchant find them.
     */
    public function testPreflightFailureIsLoggedPerShipment(): void
    {
        $logWriter = $this->createMockWithProps(LogWriter::class);
        $logWriter->expects($this->once())
            ->method('logError')
            ->with(Log::ACTION_SUBMIT, self::ORDER_NUMBER, ['orderNumber' => self::ORDER_NUMBER], 'Already submitted.');

        $this->processWithSubmitException(
            new PacketSubmitLocalizedException(__('Already submitted.')),
            $logWriter
        );
    }

    /**
     * API submission faults are already recorded with full detail by PacketSubmitter, so the
     * consumer must not create a duplicate grid record for them.
     */
    public function testApiSubmissionFaultIsNotLoggedTwice(): void
    {
        $logWriter = $this->createMockWithProps(LogWriter::class);
        $logWriter->expects($this->never())
            ->method('logError');

        $this->processWithSubmitException(
            new PacketSubmissionException('API rejected the packet.'),
            $logWriter
        );
    }

    private function processWithSubmitException(\Throwable $submitException, LogWriter $logWriter): void
    {
        $packeteryOrder = $this->createMockWithProps(Order::class);
        $packeteryOrder->method('getOrderNumber')->willReturn(self::ORDER_NUMBER);

        $collection = $this->createMockWithProps(Collection::class);
        $collection->method('getItems')->willReturn([$packeteryOrder]);

        $collectionFactory = $this->createStub(CollectionFactory::class);
        $collectionFactory->method('create')->willReturn($collection);

        $magentoOrder = $this->createMockWithProps(MagentoOrder::class);
        $magentoOrder->method('loadByIncrementId')->willReturn($magentoOrder);
        $magentoOrder->method('getId')->willReturn(5);

        $magentoOrderFactory = $this->createStub(OrderFactory::class);
        $magentoOrderFactory->method('create')->willReturn($magentoOrder);

        $packetSubmitter = $this->createMockWithProps(PacketSubmitter::class);
        $packetSubmitter->method('submitPacket')->willThrowException($submitException);

        $logger = $this->createMockWithProps(BulkPacketSubmitLogger::class);
        $logger->expects($this->once())->method('error');

        $consumer = new BulkPacketSubmitConsumer(
            $collectionFactory,
            $magentoOrderFactory,
            $packetSubmitter,
            $logger,
            $logWriter
        );

        $consumer->process('5');
    }
}
