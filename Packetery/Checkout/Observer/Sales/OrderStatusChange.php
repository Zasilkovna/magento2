<?php

declare(strict_types=1);

namespace Packetery\Checkout\Observer\Sales;

class OrderStatusChange implements \Magento\Framework\Event\ObserverInterface
{
    private const CONFIG_AUTO_SUBMIT_ENABLED = 'carriers/packetery/auto_submit_enabled';
    private const CONFIG_AUTO_SUBMIT_STATUS_MAP = 'carriers/packetery/auto_submit_status_map';

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    private $scopeConfig;

    /** @var \Packetery\Checkout\Model\Packet\PacketSubmitter */
    private $packetSubmitter;

    /** @var \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory */
    private $orderCollectionFactory;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Packetery\Checkout\Model\Packet\PacketSubmitter $packetSubmitter,
        \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->packetSubmitter = $packetSubmitter;
        $this->orderCollectionFactory = $orderCollectionFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer): void
    {
        if ($this->scopeConfig->isSetFlag(self::CONFIG_AUTO_SUBMIT_ENABLED) === false) {
            return;
        }

        /** @var \Magento\Sales\Model\Order $magentoOrder */
        $magentoOrder = $observer->getEvent()->getOrder();
        if ($magentoOrder->getOrigData('status') === $magentoOrder->getStatus()) {
            return;
        }

        $triggerStatus = $this->resolveTriggerStatus($magentoOrder->getPayment()->getMethod());
        if ($triggerStatus === null || $magentoOrder->getStatus() !== $triggerStatus) {
            return;
        }

        if (!\Packetery\Checkout\Model\Carrier\ShippingRateCode::isPacketery($magentoOrder->getShippingMethod())) {
            return;
        }

        /** @var \Packetery\Checkout\Model\ResourceModel\Order\Collection $collection */
        $collection = $this->orderCollectionFactory->create();
        $collection->addFieldToFilter('order_number', $magentoOrder->getIncrementId());

        /** @var \Packetery\Checkout\Model\Order $packeteryOrder */
        $packeteryOrder = $collection->fetchItem();
        if (!$packeteryOrder) {
            return;
        }

        try {
            $this->packetSubmitter->submitPacket($packeteryOrder, $magentoOrder);
        } catch (\Packetery\Checkout\Model\Api\PacketSubmissionException|\Packetery\Checkout\Model\Packet\PacketSubmitLocalizedException $e) {
            // noop - later
        }
    }

    private function resolveTriggerStatus(string $paymentMethod): ?string
    {
        $raw = $this->scopeConfig->getValue(self::CONFIG_AUTO_SUBMIT_STATUS_MAP);
        if (empty($raw)) {
            return null;
        }

        $mapping = json_decode($raw, true);
        if (!is_array($mapping)) {
            return null;
        }

        foreach ($mapping as $row) {
            if (($row['payment_method'] ?? '') === $paymentMethod) {
                return ($row['order_status'] ?? null) ?: null;
            }
        }

        return null;
    }
}
