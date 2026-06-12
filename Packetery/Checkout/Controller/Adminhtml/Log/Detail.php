<?php

declare(strict_types=1);

namespace Packetery\Checkout\Controller\Adminhtml\Log;

class Detail extends \Magento\Backend\App\Action
{
    public const ADMIN_RESOURCE = 'Packetery_Checkout::log';

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        private readonly \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        private readonly \Packetery\Checkout\Model\LogRepository $logRepository
    ) {
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\View\Result\Page|\Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $id = (int) $this->getRequest()->getParam('id');
        $resultRedirect = $this->resultRedirectFactory->create()->setPath('packetery/log/index');
        if ($id <= 0) {
            $this->messageManager->addErrorMessage(__('Log record not found.'));
            return $resultRedirect;
        }

        $log = $this->logRepository->findById($id);
        if ($log === null || $log->getStatus() !== \Packetery\Checkout\Model\Log::STATUS_ERROR) {
            $this->messageManager->addErrorMessage(__('Log record not found.'));
            return $resultRedirect;
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('API log'));
        $block = $resultPage->getLayout()->getBlock('packetery.log.detail');
        if ($block instanceof \Packetery\Checkout\Block\Adminhtml\Log\Detail) {
            $block->setData('log', $log);
        }

        return $resultPage;
    }
}
