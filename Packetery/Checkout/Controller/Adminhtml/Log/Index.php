<?php

declare(strict_types=1);

namespace Packetery\Checkout\Controller\Adminhtml\Log;

class Index extends \Magento\Backend\App\Action
{
    public const ADMIN_RESOURCE = 'Packetery_Checkout::log';

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        private readonly \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Packetery_Checkout::log');
        $resultPage->getConfig()->getTitle()->prepend(__('Log'));

        return $resultPage;
    }
}
