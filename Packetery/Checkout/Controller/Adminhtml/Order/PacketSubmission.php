<?php

declare(strict_types=1);

namespace Packetery\Checkout\Controller\Adminhtml\Order;

use Magento\Framework\Controller\AbstractResult;

class PacketSubmission extends \Magento\Backend\App\Action
{
    /**
     * Detail constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        protected \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        private readonly \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Magento execute.
     *
     * @return \Magento\Framework\Controller\AbstractResult
     */
    public function execute(): AbstractResult
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Packetery_Checkout::orders');
        $resultPage->getConfig()->getTitle()->prepend(__('Packet Submission'));

        $orderNumber = $this->getRequest()->getParam('order_number');
        $orderCollection = $this->orderCollectionFactory->create();
        $order = $orderCollection->getItemByColumnValue('order_number', $orderNumber);
        if (empty($order)) {
            $this->messageManager->addErrorMessage(__('Page not found'));

            return $this->resultRedirectFactory->create()->setPath('*/*/index');
        }

        return $resultPage;
    }
}
