<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit\Packet;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class PacketStatusSynchronizerTest extends \PHPUnit\Framework\TestCase
{
    public function testMissingApiPasswordIsSkipped(): void
    {
        $soapApiClient = $this->createMock(\Packetery\Checkout\Model\Api\SoapApiClient::class);
        $soapApiClient->expects($this->never())->method('packetStatus');

        $packetRepository = $this->createMock(\Packetery\Checkout\Model\PacketRepository::class);
        $packetRepository->expects($this->never())->method('save');

        $logger = $this->createMock(\Packetery\Checkout\Logger\PacketStatusSyncLogger::class);
        $logger->expects($this->once())->method('error');

        $synchronizer = new \Packetery\Checkout\Model\Packet\PacketStatusSynchronizer(
            $soapApiClient,
            $this->createCarrierFacade(null),
            $packetRepository,
            $logger,
            new \Packetery\Checkout\Test\Unit\Misc\FrozenClock(
                new \DateTimeImmutable('2026-06-05 12:00:00', new \DateTimeZone('UTC'))
            )
        );
        $synchronizer->syncStatus($this->createPacketStub('Z1', null), 1);
    }

    public function testPacketIdFaultSetsFlagAndSaves(): void
    {
        $now = new \DateTimeImmutable('2026-06-05 12:00:00', new \DateTimeZone('UTC'));

        $packet = $this->createMock(\Packetery\Checkout\Model\Packet::class);
        $packet->method('getPacketNumber')->willReturn('Z1');
        $packet->expects($this->once())->method('setPacketIdFault')->with(true);
        $packet->expects($this->once())->method('setStatusSyncedAt')->with($now);
        $packet->expects($this->never())->method('setPacketStatus');

        $packetRepository = $this->createMock(\Packetery\Checkout\Model\PacketRepository::class);
        $packetRepository->expects($this->once())->method('save')->with($packet);

        $logger = $this->createMock(\Packetery\Checkout\Logger\PacketStatusSyncLogger::class);
        $logger->expects($this->never())->method('error');

        $result = new \Packetery\Checkout\Model\Api\Result\PacketStatusResult();
        $result->setFault(\Packetery\Checkout\Model\Api\Result\PacketStatusResult::FAULT_PACKET_ID);

        $synchronizer = new \Packetery\Checkout\Model\Packet\PacketStatusSynchronizer(
            $this->createSoapApiClient($result),
            $this->createCarrierFacade('pwd'),
            $packetRepository,
            $logger,
            new \Packetery\Checkout\Test\Unit\Misc\FrozenClock($now)
        );
        $synchronizer->syncStatus($packet, 1);
    }

    public function testTransientFaultKeepsStatus(): void
    {
        $packet = $this->createMock(\Packetery\Checkout\Model\Packet::class);
        $packet->method('getPacketNumber')->willReturn('Z1');
        $packet->expects($this->never())->method('setPacketStatus');
        $packet->expects($this->never())->method('setPacketIdFault');
        $packet->expects($this->never())->method('setStatusSyncedAt');

        $packetRepository = $this->createMock(\Packetery\Checkout\Model\PacketRepository::class);
        $packetRepository->expects($this->never())->method('save');

        $logger = $this->createMock(\Packetery\Checkout\Logger\PacketStatusSyncLogger::class);
        $logger->expects($this->once())->method('error');

        $result = new \Packetery\Checkout\Model\Api\Result\PacketStatusResult();
        $result->setFault('SenderNotExists');

        $synchronizer = new \Packetery\Checkout\Model\Packet\PacketStatusSynchronizer(
            $this->createSoapApiClient($result),
            $this->createCarrierFacade('pwd'),
            $packetRepository,
            $logger,
            new \Packetery\Checkout\Test\Unit\Misc\FrozenClock(
                new \DateTimeImmutable('2026-06-05 12:00:00', new \DateTimeZone('UTC'))
            )
        );
        $synchronizer->syncStatus($packet, 1);
    }

    public function testChangedStatusIsSaved(): void
    {
        $now = new \DateTimeImmutable('2026-06-05 12:00:00', new \DateTimeZone('UTC'));

        $packet = $this->createMock(\Packetery\Checkout\Model\Packet::class);
        $packet->method('getPacketNumber')->willReturn('Z1');
        $packet->method('getPacketStatus')->willReturn(\Packetery\Checkout\Model\Packet\PacketStatus::ARRIVED);
        $packet->expects($this->once())
            ->method('setPacketStatus')
            ->with(\Packetery\Checkout\Model\Packet\PacketStatus::DELIVERED);
        $packet->expects($this->once())->method('setStatusSyncedAt')->with($now);

        $packetRepository = $this->createMock(\Packetery\Checkout\Model\PacketRepository::class);
        $packetRepository->expects($this->once())->method('save')->with($packet);

        $result = new \Packetery\Checkout\Model\Api\Result\PacketStatusResult();
        $result->setCodeText(\Packetery\Checkout\Model\Packet\PacketStatus::DELIVERED);

        $synchronizer = new \Packetery\Checkout\Model\Packet\PacketStatusSynchronizer(
            $this->createSoapApiClient($result),
            $this->createCarrierFacade('pwd'),
            $packetRepository,
            $this->createMock(\Packetery\Checkout\Logger\PacketStatusSyncLogger::class),
            new \Packetery\Checkout\Test\Unit\Misc\FrozenClock($now)
        );
        $synchronizer->syncStatus($packet, 1);
    }

    public function testUnchangedStatusStillBumpsSyncedAt(): void
    {
        $now = new \DateTimeImmutable('2026-06-05 12:00:00', new \DateTimeZone('UTC'));

        $packet = $this->createMock(\Packetery\Checkout\Model\Packet::class);
        $packet->method('getPacketNumber')->willReturn('Z1');
        $packet->method('getPacketStatus')->willReturn(\Packetery\Checkout\Model\Packet\PacketStatus::DELIVERED);
        $packet->expects($this->never())->method('setPacketStatus');
        $packet->expects($this->once())->method('setStatusSyncedAt')->with($now);

        $packetRepository = $this->createMock(\Packetery\Checkout\Model\PacketRepository::class);
        $packetRepository->expects($this->once())->method('save')->with($packet);

        $result = new \Packetery\Checkout\Model\Api\Result\PacketStatusResult();
        $result->setCodeText(\Packetery\Checkout\Model\Packet\PacketStatus::DELIVERED);

        $synchronizer = new \Packetery\Checkout\Model\Packet\PacketStatusSynchronizer(
            $this->createSoapApiClient($result),
            $this->createCarrierFacade('pwd'),
            $packetRepository,
            $this->createMock(\Packetery\Checkout\Logger\PacketStatusSyncLogger::class),
            new \Packetery\Checkout\Test\Unit\Misc\FrozenClock($now)
        );
        $synchronizer->syncStatus($packet, 1);
    }

    private function createSoapApiClient(
        \Packetery\Checkout\Model\Api\Result\PacketStatusResult $result
    ): \Packetery\Checkout\Model\Api\SoapApiClient {
        $soapApiClient = $this->createStub(\Packetery\Checkout\Model\Api\SoapApiClient::class);
        $soapApiClient->method('packetStatus')->willReturn($result);
        return $soapApiClient;
    }

    private function createCarrierFacade(?string $apiPassword): \Packetery\Checkout\Model\Carrier\Facade
    {
        $config = $this->createStub(\Packetery\Checkout\Model\Carrier\Imp\Packetery\Config::class);
        $config->method('getApiPassword')->willReturn($apiPassword);

        $carrierFacade = $this->createStub(\Packetery\Checkout\Model\Carrier\Facade::class);
        $carrierFacade->method('getPacketeryCarrierConfig')->willReturn($config);
        return $carrierFacade;
    }

    private function createPacketStub(string $packetNumber, ?string $packetStatus): \Packetery\Checkout\Model\Packet
    {
        $packet = $this->createStub(\Packetery\Checkout\Model\Packet::class);
        $packet->method('getPacketNumber')->willReturn($packetNumber);
        $packet->method('getPacketStatus')->willReturn($packetStatus);
        return $packet;
    }
}
