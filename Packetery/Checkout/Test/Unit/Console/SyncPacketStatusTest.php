<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit\Console;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class SyncPacketStatusTest extends \PHPUnit\Framework\TestCase
{
    public function testQueuesEachOpenPacket(): void
    {
        $packetRepository = $this->createMock(\Packetery\Checkout\Model\PacketRepository::class);
        $packetRepository->expects($this->once())->method('getOpenPacketIds')->willReturn([10, 20]);

        $publisher = $this->createMock(\Packetery\Checkout\Model\Packet\PacketStatusSyncPublisher::class);
        $publisher->expects($this->exactly(2))->method('publish');

        $command = new \Packetery\Checkout\Console\Command\SyncPacketStatus(
            $packetRepository,
            $this->createStub(\Packetery\Checkout\Model\Packet\PacketStatusProvider::class),
            $publisher,
            $this->createDeploymentConfig(0)
        );
        $command->runForCron();
    }

    public function testPassesDeployConfigBatchSizeToRepository(): void
    {
        $packetRepository = $this->createMock(\Packetery\Checkout\Model\PacketRepository::class);
        $packetRepository->expects($this->once())
            ->method('getOpenPacketIds')
            ->with($this->anything(), 200)
            ->willReturn([]);

        $command = new \Packetery\Checkout\Console\Command\SyncPacketStatus(
            $packetRepository,
            $this->createStub(\Packetery\Checkout\Model\Packet\PacketStatusProvider::class),
            $this->createMock(\Packetery\Checkout\Model\Packet\PacketStatusSyncPublisher::class),
            $this->createDeploymentConfig(200)
        );
        $command->runForCron();
    }

    /**
     * @param int $batchSize value returned for the status-polling "batch_size" deployment key
     */
    private function createDeploymentConfig(int $batchSize): \Magento\Framework\App\DeploymentConfig
    {
        $deploymentConfig = $this->createStub(\Magento\Framework\App\DeploymentConfig::class);
        $deploymentConfig->method('get')->willReturnCallback(
            static function (string $path) use ($batchSize) {
                return $path === 'packetery/status_polling/batch_size' ? $batchSize : null;
            }
        );
        return $deploymentConfig;
    }
}
