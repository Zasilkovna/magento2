<?php

declare(strict_types=1);

namespace Packetery\Checkout\Controller\Adminhtml\Packet;

use Magento\Backend\App\Action;
use Packetery\Checkout\Model\Misc\ComboPhrase;

class Submit extends Action
{
    public const ADMIN_RESOURCE = 'Packetery_Checkout::packetery';

    /** @var \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory */
    private $orderCollectionFactory;

    /** @var \Magento\Sales\Model\OrderFactory */
    private $magentoOrderFactory;

    /** @var \Packetery\Checkout\Model\Packet\PacketSubmitter */
    private $packetSubmitter;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Sales\Model\OrderFactory $magentoOrderFactory,
        \Packetery\Checkout\Model\Packet\PacketSubmitter $packetSubmitter
    ) {
        parent::__construct($context);
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->magentoOrderFactory = $magentoOrderFactory;
        $this->packetSubmitter = $packetSubmitter;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute(): \Magento\Framework\Controller\Result\Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create()->setPath('packetery/order/index');
        $orderId = (int) $this->getRequest()->getParam('order_id');
        if ($orderId <= 0) {
            $this->messageManager->addErrorMessage(__('Page not found'));
            return $resultRedirect;
        }

        $collection = $this->orderCollectionFactory->create();
        $collection->addFilter('id', $orderId);
        $items = ($collection->getItems() ?: []);
        $packeteryOrder = array_shift($items);
        if (!$packeteryOrder instanceof \Packetery\Checkout\Model\Order) {
            $this->messageManager->addErrorMessage(__('Page not found'));
            return $resultRedirect;
        }

        $magentoOrder = $this->magentoOrderFactory->create()->loadByIncrementId($packeteryOrder->getOrderNumber());
        if (!$magentoOrder->getId()) {
            $this->messageManager->addErrorMessage(__('Order %1 not found.', $packeteryOrder->getOrderNumber()));
            return $resultRedirect;
        }

        try {
            $this->packetSubmitter->submitPacket($packeteryOrder, $magentoOrder);
            $this->messageManager->addSuccessMessage(__('The packet was successfully submitted.'));
        } catch (\Packetery\Checkout\Model\Api\PacketSubmissionException $exception) {
            $errors = $exception->getSoapDetailErrors();
            $firstMessage = $errors[0] ?? $exception->getMessage();

            $this->messageManager->addErrorMessage(
                new ComboPhrase(
                    [
                        __('The packet could not be submitted to Packeta.'),
                        ' ',
                        $firstMessage,
                    ]
                )
            );
        } catch (\Packetery\Checkout\Model\Packet\PacketSubmitLocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }

        return $resultRedirect;
    }
}
