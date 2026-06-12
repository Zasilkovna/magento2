<?php

declare(strict_types=1);

namespace Packetery\Checkout\Console\Command;

class SyncPacketStatus extends \Symfony\Component\Console\Command\Command
{
    private const BATCH_SIZE_CONFIG_PATH = 'packetery/status_polling/batch_size';

    /** @var \Packetery\Checkout\Model\PacketRepository */
    private $packetRepository;

    /** @var \Packetery\Checkout\Model\Packet\PacketStatusProvider */
    private $packetStatusProvider;

    /** @var \Packetery\Checkout\Model\Packet\PacketStatusSyncPublisher */
    private $packetStatusSyncPublisher;

    /** @var \Magento\Framework\App\DeploymentConfig */
    private $deploymentConfig;

    public function __construct(
        \Packetery\Checkout\Model\PacketRepository $packetRepository,
        \Packetery\Checkout\Model\Packet\PacketStatusProvider $packetStatusProvider,
        \Packetery\Checkout\Model\Packet\PacketStatusSyncPublisher $packetStatusSyncPublisher,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig
    ) {
        parent::__construct();
        $this->packetRepository = $packetRepository;
        $this->packetStatusProvider = $packetStatusProvider;
        $this->packetStatusSyncPublisher = $packetStatusSyncPublisher;
        $this->deploymentConfig = $deploymentConfig;
    }

    protected function configure(): void
    {
        $this->setName('packetery:sync-packet-status');
        $this->setDescription('Queue open Packeta packets for asynchronous status synchronization');

        parent::configure();
    }

    protected function execute(
        \Symfony\Component\Console\Input\InputInterface $input,
        \Symfony\Component\Console\Output\OutputInterface $output
    ): int {
        $output->writeln('Packet status synchronization started');

        $batchSize = (int) $this->deploymentConfig->get(self::BATCH_SIZE_CONFIG_PATH);
        $openPacketIds = $this->packetRepository->getOpenPacketIds(
            $this->packetStatusProvider->getFinalCodeTexts(),
            $batchSize
        );

        $queuedCount = 0;
        foreach ($openPacketIds as $packetId) {
            $this->packetStatusSyncPublisher->publish($packetId);
            $queuedCount++;
        }

        $output->writeln("Queued {$queuedCount} packet(s) for status synchronization");

        return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
    }

    public function runForCron(): int
    {
        return $this->run(new \Symfony\Component\Console\Input\ArrayInput([]), new \Symfony\Component\Console\Output\NullOutput());
    }
}
