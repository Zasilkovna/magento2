<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Packet;

use Magento\Sales\Model\OrderFactory;
use Packetery\Checkout\Logger\BulkPacketSubmitLogger;
use Packetery\Checkout\Model\Order;
use Packetery\Checkout\Model\OrderRepository;

class BulkPacketSubmitConsumer
{
    /** @var OrderRepository */
    private $orderRepository;

    /** @var OrderFactory */
    private $magentoOrderFactory;

    /** @var PacketSubmitter */
    private $packetSubmitter;

    /** @var BulkPacketSubmitLogger */
    private $logger;

    public function __construct(
        OrderRepository $orderRepository,
        OrderFactory $magentoOrderFactory,
        PacketSubmitter $packetSubmitter,
        BulkPacketSubmitLogger $logger
    ) {
        $this->orderRepository = $orderRepository;
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

        $packeteryOrder = $this->orderRepository->findById((int) $packeteryOrderId);
        if (!$packeteryOrder instanceof Order) {
            return;
        }

        $magentoOrder = $this->magentoOrderFactory->create()->loadByIncrementId($packeteryOrder->getOrderNumber());
        if (!$magentoOrder->getId()) {
            return;
        }

        try {
            $this->packetSubmitter->submitPacket($packeteryOrder, $magentoOrder);
        } catch (\Packetery\Checkout\Model\Api\PacketSubmissionException) {
            return;
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
}

