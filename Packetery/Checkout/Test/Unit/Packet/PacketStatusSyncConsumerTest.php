<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit\Packet;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class PacketStatusSyncConsumerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function invalidPacketIdProvider(): array
    {
        return [
            'non-numeric' => ['abc'],
            'empty string' => [''],
            'zero' => ['0'],
            'negative' => ['-1'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidPacketIdProvider')]
    public function testInvalidPacketIdIsLoggedAndSkipped(string $packetId): void
    {
        $packetRepository = $this->createMock(\Packetery\Checkout\Model\PacketRepository::class);
        $packetRepository->expects($this->never())->method('findById');

        $orderFactory = $this->createMock(\Magento\Sales\Model\OrderFactory::class);
        $orderFactory->expects($this->never())->method('create');

        $synchronizer = $this->createMock(\Packetery\Checkout\Model\Packet\PacketStatusSynchronizer::class);
        $synchronizer->expects($this->never())->method('syncStatus');

        $logger = $this->createMock(\Packetery\Checkout\Logger\PacketStatusSyncLogger::class);
        $logger->expects($this->once())->method('error');

        $this->createConsumer($packetRepository, $orderFactory, $synchronizer, $logger)->process($packetId);
    }

    public function testMissingPacketIsSkipped(): void
    {
        $packetRepository = $this->createMock(\Packetery\Checkout\Model\PacketRepository::class);
        $packetRepository->method('findById')->willReturn(null);

        $orderFactory = $this->createMock(\Magento\Sales\Model\OrderFactory::class);
        $orderFactory->expects($this->never())->method('create');

        $synchronizer = $this->createMock(\Packetery\Checkout\Model\Packet\PacketStatusSynchronizer::class);
        $synchronizer->expects($this->never())->method('syncStatus');

        $logger = $this->createMock(\Packetery\Checkout\Logger\PacketStatusSyncLogger::class);
        $logger->expects($this->never())->method('error');
        $logger->expects($this->never())->method('warning');

        $this->createConsumer($packetRepository, $orderFactory, $synchronizer, $logger)->process('5');
    }

    public function testOrphanedPacketLogsWarningAndSkipsSync(): void
    {
        $packetRepository = $this->createMock(\Packetery\Checkout\Model\PacketRepository::class);
        $packetRepository->method('findById')->willReturn($this->createPacketStub(5, '00012345', 'Z1'));

        $synchronizer = $this->createMock(\Packetery\Checkout\Model\Packet\PacketStatusSynchronizer::class);
        $synchronizer->expects($this->never())->method('syncStatus');

        $logger = $this->createMock(\Packetery\Checkout\Logger\PacketStatusSyncLogger::class);
        $logger->expects($this->once())->method('warning');
        $logger->expects($this->never())->method('error');

        $this->createConsumer(
            $packetRepository,
            $this->createOrderFactory(null, 0),
            $synchronizer,
            $logger
        )->process('5');
    }

    public function testSynchronizationExceptionIsCaughtAndLogged(): void
    {
        $packet = $this->createPacketStub(5, '00012345', 'Z1');

        $packetRepository = $this->createMock(\Packetery\Checkout\Model\PacketRepository::class);
        $packetRepository->method('findById')->willReturn($packet);

        $synchronizer = $this->createMock(\Packetery\Checkout\Model\Packet\PacketStatusSynchronizer::class);
        $synchronizer->expects($this->once())
            ->method('syncStatus')
            ->with($packet, 7)
            ->willThrowException(new \RuntimeException('boom'));

        $logger = $this->createMock(\Packetery\Checkout\Logger\PacketStatusSyncLogger::class);
        $logger->expects($this->once())->method('error');

        $this->createConsumer(
            $packetRepository,
            $this->createOrderFactory(42, 7),
            $synchronizer,
            $logger
        )->process('5');
    }

    public function testSuccessfulSynchronization(): void
    {
        $packet = $this->createPacketStub(5, '00012345', 'Z1');

        $packetRepository = $this->createMock(\Packetery\Checkout\Model\PacketRepository::class);
        $packetRepository->method('findById')->willReturn($packet);

        $synchronizer = $this->createMock(\Packetery\Checkout\Model\Packet\PacketStatusSynchronizer::class);
        $synchronizer->expects($this->once())->method('syncStatus')->with($packet, 7);

        $logger = $this->createMock(\Packetery\Checkout\Logger\PacketStatusSyncLogger::class);
        $logger->expects($this->never())->method('error');
        $logger->expects($this->never())->method('warning');

        $this->createConsumer(
            $packetRepository,
            $this->createOrderFactory(42, 7),
            $synchronizer,
            $logger
        )->process('5');
    }

    private function createConsumer(
        \Packetery\Checkout\Model\PacketRepository $packetRepository,
        \Magento\Sales\Model\OrderFactory $magentoOrderFactory,
        \Packetery\Checkout\Model\Packet\PacketStatusSynchronizer $packetStatusSynchronizer,
        \Packetery\Checkout\Logger\PacketStatusSyncLogger $logger
    ): \Packetery\Checkout\Model\Packet\PacketStatusSyncConsumer {
        return new \Packetery\Checkout\Model\Packet\PacketStatusSyncConsumer(
            $packetRepository,
            $magentoOrderFactory,
            $packetStatusSynchronizer,
            $logger
        );
    }

    private function createPacketStub(int $id, string $orderNumber, string $packetNumber): \Packetery\Checkout\Model\Packet
    {
        $packet = $this->createStub(\Packetery\Checkout\Model\Packet::class);
        $packet->method('getId')->willReturn($id);
        $packet->method('getOrderNumber')->willReturn($orderNumber);
        $packet->method('getPacketNumber')->willReturn($packetNumber);
        return $packet;
    }

    private function createOrderFactory(?int $orderId, int $storeId): \Magento\Sales\Model\OrderFactory
    {
        $order = $this->createStub(\Magento\Sales\Model\Order::class);
        $order->method('loadByIncrementId')->willReturn($order);
        $order->method('getId')->willReturn($orderId);
        $order->method('getStoreId')->willReturn($storeId);

        $orderFactory = $this->createStub(\Magento\Sales\Model\OrderFactory::class);
        $orderFactory->method('create')->willReturn($order);
        return $orderFactory;
    }
}
