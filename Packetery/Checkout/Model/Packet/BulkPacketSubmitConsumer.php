<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Packet;

use Magento\Sales\Model\OrderFactory;
use Packetery\Checkout\Logger\BulkPacketSubmitLogger;
use Packetery\Checkout\Model\Order;
use Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory as PacketeryOrderCollectionFactory;

class BulkPacketSubmitConsumer
{
    /** @var PacketeryOrderCollectionFactory */
    private $packeteryOrderCollectionFactory;

    /** @var OrderFactory */
    private $magentoOrderFactory;

    /** @var PacketSubmitter */
    private $packetSubmitter;

    /** @var BulkPacketSubmitLogger */
    private $logger;

    public function __construct(
        PacketeryOrderCollectionFactory $packeteryOrderCollectionFactory,
        OrderFactory $magentoOrderFactory,
        PacketSubmitter $packetSubmitter,
        BulkPacketSubmitLogger $logger
    ) {
        $this->packeteryOrderCollectionFactory = $packeteryOrderCollectionFactory;
        $this->magentoOrderFactory = $magentoOrderFactory;
        $this->packetSubmitter = $packetSubmitter;
        $this->logger = $logger;
    }

    public function process(string $packeteryOrderId): void
    {
        if (!is_numeric($packeteryOrderId) || (int) $packeteryOrderId <= 0) {
            $this->logger->error('Invalid packetery order ID received.', ['packetery_order_id' => $packeteryOrderId]);
            return;
        }

        $packeteryOrder = $this->loadPacketeryOrder((int) $packeteryOrderId);
        if (!$packeteryOrder instanceof Order) {
            return;
        }

        $magentoOrder = $this->magentoOrderFactory->create()->loadByIncrementId($packeteryOrder->getOrderNumber());
        if (!$magentoOrder->getId()) {
            return;
        }

        try {
            $this->packetSubmitter->submitPacket($packeteryOrder, $magentoOrder);
        } catch (\Throwable $exception) {
            $this->logger->error(
                'Bulk shipment submission failed.',
                [
                    'packetery_order_id' => $packeteryOrderId,
                    'order_number' => $packeteryOrder->getOrderNumber(),
                    'exception' => $exception,
                ]
            );
        }
    }

    private function loadPacketeryOrder(int $packeteryOrderId): ?Order
    {
        if ($packeteryOrderId <= 0) {
            return null;
        }

        $collection = $this->packeteryOrderCollectionFactory->create();
        $collection->addFilter('id', $packeteryOrderId);
        $items = $collection->getItems() ?: [];
        $packeteryOrder = array_shift($items);
        if (!$packeteryOrder instanceof Order) {
            return null;
        }

        return $packeteryOrder;
    }
}

