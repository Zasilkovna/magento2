<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Packet;

use Magento\Framework\MessageQueue\PublisherInterface;

class BulkPacketSubmitPublisher
{
    public const TOPIC_NAME = 'packetery.checkout.packet.submit';

    /** @var PublisherInterface */
    private $publisher;

    public function __construct(PublisherInterface $publisher)
    {
        $this->publisher = $publisher;
    }

    public function publish(int $packeteryOrderId): void
    {
        $this->publisher->publish(self::TOPIC_NAME, (string) $packeteryOrderId);
    }
}

