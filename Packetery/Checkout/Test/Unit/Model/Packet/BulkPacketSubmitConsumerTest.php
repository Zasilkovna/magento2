<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit\Model\Packet;

use Magento\Sales\Model\Order as MagentoOrder;
use Magento\Sales\Model\OrderFactory;
use Packetery\Checkout\Logger\BulkPacketSubmitLogger;
use Packetery\Checkout\Model\Api\PacketSubmissionException;
use Packetery\Checkout\Model\Order;
use Packetery\Checkout\Model\OrderRepository;
use Packetery\Checkout\Model\Packet\BulkPacketSubmitConsumer;
use Packetery\Checkout\Model\Packet\PacketSubmitLocalizedException;
use Packetery\Checkout\Model\Packet\PacketSubmitter;
use Packetery\Checkout\Test\BaseTest;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;

#[AllowMockObjectsWithoutExpectations]
class BulkPacketSubmitConsumerTest extends BaseTest
{
    private const ORDER_NUMBER = '100000123';

    /** @return array<string, array{\Throwable, bool}> */
    public static function failureProvider(): array
    {
        return [
            // Expected guard that slipped past the controller pre-flight (race safeguard) -> file log
            'guard failure' => [new PacketSubmitLocalizedException(__('Already submitted.')), true],
            // Genuinely unexpected runtime error -> file log
            'unexpected error' => [new \RuntimeException('Boom.'), true],
            // API fault is already recorded in the API log by PacketSubmitter -> not traced again
            'api fault' => [new PacketSubmissionException('API rejected the packet.'), false],
        ];
    }

    /**
     * The consumer never writes to the API log: the controller pre-flight handles the expected cases up
     * front, and API faults are already recorded by PacketSubmitter. Only the guard safeguard and
     * unexpected errors are traced to the file log; an API fault is left alone (it is already logged).
     */
    #[DataProvider('failureProvider')]
    public function testFailureLoggingByType(\Throwable $submitException, bool $expectFileLog): void
    {
        $packeteryOrder = $this->createMockWithProps(Order::class);
        $packeteryOrder->method('getOrderNumber')->willReturn(self::ORDER_NUMBER);

        $orderRepository = $this->createStub(OrderRepository::class);
        $orderRepository->method('findById')->willReturn($packeteryOrder);

        $magentoOrder = $this->createMockWithProps(MagentoOrder::class);
        $magentoOrder->method('loadByIncrementId')->willReturn($magentoOrder);
        $magentoOrder->method('getId')->willReturn(5);

        $magentoOrderFactory = $this->createStub(OrderFactory::class);
        $magentoOrderFactory->method('create')->willReturn($magentoOrder);

        $packetSubmitter = $this->createMockWithProps(PacketSubmitter::class);
        $packetSubmitter->method('submitPacket')->willThrowException($submitException);

        $logger = $this->createMockWithProps(BulkPacketSubmitLogger::class);
        $logger->expects($expectFileLog ? $this->once() : $this->never())->method('error');

        $consumer = new BulkPacketSubmitConsumer(
            $orderRepository,
            $magentoOrderFactory,
            $packetSubmitter,
            $logger
        );

        $consumer->process('5');
    }
}
