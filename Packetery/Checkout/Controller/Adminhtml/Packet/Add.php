<?php

declare(strict_types=1);

namespace Packetery\Checkout\Controller\Adminhtml\Packet;

class Add extends \Magento\Backend\App\Action
{
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        protected \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        private readonly \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        private readonly \Magento\Sales\Api\OrderRepositoryInterface $magentoOrderRepository
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\AbstractResult
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Packetery_Checkout::orders');
        $resultPage->getConfig()->getTitle()->prepend(__('New Packet'));

        $orderId = (int)$this->getRequest()->getParam('order_id');
        try {
            $magentoOrder = $this->magentoOrderRepository->get($orderId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException) {
            return $this->redirectToOrderView($orderId);
        }

        $orderCollection = $this->orderCollectionFactory->create();
        $order = $orderCollection->getItemByColumnValue('order_number', $magentoOrder->getIncrementId());

        if ($order === null) {
            $this->messageManager->addErrorMessage(__('Page not found'));

            return $this->redirectToOrderView($orderId);
        }

        return $resultPage;
    }

    private function redirectToOrderView(int $orderId): \Magento\Framework\Controller\Result\Redirect
    {
        return $this->resultRedirectFactory->create()->setPath('sales/order/view', ['order_id' => $orderId]);
    }
}
