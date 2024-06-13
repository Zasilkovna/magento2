<?php

namespace Packetery\Checkout\Controller\Adminhtml\Order;

use Magento\Framework\Controller\AbstractResult;

class PacketSubmission extends \Magento\Backend\App\Action
{
    /**
     * Detail constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        protected \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        private readonly \Magento\Sales\Model\OrderFactory $orderFactory,
        private readonly \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        private \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    ) {
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\AbstractResult
     */
    public function execute(): AbstractResult
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Packetery_Checkout::orders');
        $resultPage->getConfig()->getTitle()->prepend(__('Packet Submission'));

        $id = $this->getRequest()->getParam('order_id');
        $magentoOrder = $this->orderRepository->get($id);
        $orderCollection = $this->orderCollectionFactory->create();
        $order = $orderCollection->getItemByColumnValue('order_number', $magentoOrder->getIncrementId());
        if (empty($order)) {
            $this->messageManager->addErrorMessage(__('Page not found'));

            return $this->resultRedirectFactory->create()->setPath('*/*/index');
        }

        $shippingMethod = $magentoOrder->getShippingMethod(true);
        if (!$shippingMethod) {
            $this->messageManager->addErrorMessage(__('Page not found'));

            return $this->resultRedirectFactory->create()->setPath('*/*/index');
        }

        return $resultPage;
    }
}
