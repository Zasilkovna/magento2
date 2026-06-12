<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Packet;

class PacketStatusSyncPublisher
{
    public const TOPIC_NAME = 'packetery.checkout.packet.status.sync';

    /** @var \Magento\Framework\MessageQueue\PublisherInterface */
    private $publisher;

    public function __construct(\Magento\Framework\MessageQueue\PublisherInterface $publisher)
    {
        $this->publisher = $publisher;
    }

    public function publish(int $packetId): void
    {
        $this->publisher->publish(self::TOPIC_NAME, (string) $packetId);
    }
}
