<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Packet;

class BulkSubmitPlanner
{
    /** @var \Packetery\Checkout\Model\OrderRepository */
    private $orderRepository;

    /** @var \Magento\Sales\Model\OrderFactory */
    private $magentoOrderFactory;

    /** @var \Packetery\Checkout\Model\Carrier\Facade */
    private $carrierFacade;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    private $storeManager;

    /** @var \Packetery\Checkout\Model\Packet\SubmitPreconditions */
    private $submitPreconditions;

    public function __construct(
        \Packetery\Checkout\Model\OrderRepository $orderRepository,
        \Magento\Sales\Model\OrderFactory $magentoOrderFactory,
        \Packetery\Checkout\Model\Carrier\Facade $carrierFacade,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Packetery\Checkout\Model\Packet\SubmitPreconditions $submitPreconditions
    ) {
        $this->orderRepository = $orderRepository;
        $this->magentoOrderFactory = $magentoOrderFactory;
        $this->carrierFacade = $carrierFacade;
        $this->storeManager = $storeManager;
        $this->submitPreconditions = $submitPreconditions;
    }

    /**
     * @param int[] $orderIds
     */
    public function plan(array $orderIds): BulkSubmitPlan
    {
        $queueableOrderIds = [];
        $alreadySubmittedCount = 0;
        $missingConfigCount = 0;
        $missingConfigStoreNames = [];

        foreach ($orderIds as $orderId) {
            $orderId = (int) $orderId;
            $packeteryOrder = $this->orderRepository->findById($orderId);
            if (!$packeteryOrder instanceof \Packetery\Checkout\Model\Order) {
                continue;
            }

            if ($this->submitPreconditions->isAlreadySubmitted($packeteryOrder->getOrderNumber())) {
                $alreadySubmittedCount++;
                continue;
            }

            $magentoOrder = $this->magentoOrderFactory->create()->loadByIncrementId($packeteryOrder->getOrderNumber());
            if (!$magentoOrder->getId()) {
                $queueableOrderIds[] = $orderId;
                continue;
            }

            $storeId = (int) $magentoOrder->getStoreId();
            if (!$this->submitPreconditions->hasRequiredConfig($this->carrierFacade->getPacketeryCarrierConfig($storeId))) {
                $missingConfigCount++;
                $storeName = $this->resolveStoreName($storeId);
                if (!in_array($storeName, $missingConfigStoreNames, true)) {
                    $missingConfigStoreNames[] = $storeName;
                }

                continue;
            }

            $queueableOrderIds[] = $orderId;
        }

        return new BulkSubmitPlan(
            $queueableOrderIds,
            $alreadySubmittedCount,
            $missingConfigCount,
            $missingConfigStoreNames
        );
    }

    private function resolveStoreName(int $storeId): string
    {
        try {
            return (string) $this->storeManager->getStore($storeId)->getName();
        } catch (\Magento\Framework\Exception\NoSuchEntityException) {
            return (string) $storeId;
        }
    }
}
