<?php

namespace Packetery\Checkout\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

class UpgradeData implements UpgradeDataInterface
{
    /** @var \Magento\Config\Model\Config\Factory */
    private $configFactory;

    /** @var \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory */
    private $orderCollectionFactory;

    /** @var \Magento\Sales\Model\OrderFactory */
    private $orderFactory;

    /**
     * @param \Magento\Config\Model\Config\Factory $configFactory
     * @param \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     */
    public function __construct(\Magento\Config\Model\Config\Factory $configFactory, \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory, \Magento\Sales\Model\OrderFactory $orderFactory) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->orderFactory = $orderFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ): void {
        if (version_compare($context->getVersion(), "2.1.0", "<")) {
            $configModel = $this->configFactory->create();
            $configModel->setDataByPath('carriers/packetery/sallowspecific', 0); // config option UI was removed
            $configModel->save();
        }

        if (version_compare($context->getVersion(), '2.3.0', '<')) {
            $orderCollection = $this->orderCollectionFactory->create();

            /** @var \Packetery\Checkout\Model\Order $packeteryOrder */
            foreach ($orderCollection->getItems() as $packeteryOrder) {
                /** @var \Magento\Sales\Model\Order $order */
                $order = $this->orderFactory->create()->loadByIncrementId($packeteryOrder->getOrderNumber());
                if (!$order) {
                    continue;
                }

                $shippingAddress = $order->getShippingAddress();
                if (!$shippingAddress) {
                    continue;
                }

                $orderCollectionItem = $this->orderCollectionFactory->create();
                $orderCollectionItem->addFilter('id', $packeteryOrder->getId());
                $orderCollectionItem->addFieldToFilter('recipient_country_id', ['null' => true]);
                $orderCollectionItem->setDataToAll(
                    [
                        'recipient_country_id' => $shippingAddress->getCountryId(),
                    ]
                );
                $orderCollectionItem->save();
            }

        }
    }
}
