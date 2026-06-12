<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Packet;

class PacketStatusSyncConsumer
{
    /** @var \Packetery\Checkout\Model\PacketRepository */
    private $packetRepository;

    /** @var \Magento\Sales\Model\OrderFactory */
    private $magentoOrderFactory;

    /** @var \Packetery\Checkout\Model\Packet\PacketStatusSynchronizer */
    private $packetStatusSynchronizer;

    /** @var \Packetery\Checkout\Logger\PacketStatusSyncLogger */
    private $logger;

    public function __construct(
        \Packetery\Checkout\Model\PacketRepository $packetRepository,
        \Magento\Sales\Model\OrderFactory $magentoOrderFactory,
        \Packetery\Checkout\Model\Packet\PacketStatusSynchronizer $packetStatusSynchronizer,
        \Packetery\Checkout\Logger\PacketStatusSyncLogger $logger
    ) {
        $this->packetRepository = $packetRepository;
        $this->magentoOrderFactory = $magentoOrderFactory;
        $this->packetStatusSynchronizer = $packetStatusSynchronizer;
        $this->logger = $logger;
    }

    public function process(string $packetId): void
    {
        if (!is_numeric($packetId) || (int) $packetId <= 0) {
            $this->logger->error('Invalid packet ID received.', ['packet_id' => $packetId]);
            return;
        }

        $packet = $this->packetRepository->findById((int) $packetId);
        if (!$packet instanceof \Packetery\Checkout\Model\Packet) {
            return;
        }

        $magentoOrder = $this->magentoOrderFactory->create()->loadByIncrementId($packet->getOrderNumber());
        if (!$magentoOrder->getId()) {
            $this->logger->warning(
                'Orphaned packet without a matching Magento order.',
                ['packet_id' => $packet->getId(), 'order_number' => $packet->getOrderNumber()]
            );
            return;
        }

        try {
            $this->packetStatusSynchronizer->syncStatus($packet, (int) $magentoOrder->getStoreId());
        } catch (\Throwable $exception) {
            $this->logger->error(
                'Packet status synchronization failed.',
                [
                    'packet_id' => $packetId,
                    'packet_number' => $packet->getPacketNumber(),
                    'exception' => $exception,
                ]
            );
        }
    }
}
