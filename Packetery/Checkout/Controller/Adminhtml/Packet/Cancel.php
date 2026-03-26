<?php

declare(strict_types=1);

namespace Packetery\Checkout\Controller\Adminhtml\Packet;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultInterface;

class Cancel extends Action
{
    public const ADMIN_RESOURCE = 'Packetery_Checkout::packetery';

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        private readonly \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        private readonly \Magento\Sales\Model\OrderFactory $magentoOrderFactory,
        private readonly \Packetery\Checkout\Model\Packet\PacketCanceler $packetCanceler
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create()->setPath('packetery/order/index');

        $orderId = (int) $this->getRequest()->getParam('order_id');
        if ($orderId <= 0) {
            $this->messageManager->addErrorMessage(__('Page not found'));

            return $resultRedirect;
        }

        $packetNumber = (string) $this->getRequest()->getParam('packet_number');
        $packetNumber = trim($packetNumber);

        $collection = $this->orderCollectionFactory->create();
        $collection->addFilter('id', $orderId);
        $items = ($collection->getItems() ?: []);
        $packeteryOrder = array_shift($items);
        if (!$packeteryOrder) {
            $this->messageManager->addErrorMessage(__('Page not found'));

            return $resultRedirect;
        }

        $magentoOrder = $this->magentoOrderFactory->create()->loadByIncrementId($packeteryOrder->getOrderNumber());
        if (!$magentoOrder->getId()) {
            $this->messageManager->addErrorMessage(__('Order %1 not found.', $packeteryOrder->getOrderNumber()));

            return $resultRedirect;
        }

        $trackingNumber = $packetNumber !== '' ? ('Z' . $packetNumber) : '';

        try {
            $this->packetCanceler->cancelPacket($packeteryOrder, $magentoOrder, $packetNumber);
            $this->messageManager->addSuccessMessage(__('The packet %1 was successfully canceled.', $trackingNumber));
        } catch (\Packetery\Checkout\Model\Packet\PacketCancelLocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }

        return $resultRedirect;
    }
}
