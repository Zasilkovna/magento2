<?php

declare(strict_types=1);

namespace Packetery\Checkout\Controller\Adminhtml\Log;

class Index extends \Magento\Backend\App\Action
{
    protected \Magento\Framework\View\Result\PageFactory $resultPageFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute(): \Magento\Backend\Model\View\Result\Page
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Packetery_Checkout::log');
        $resultPage->getConfig()->getTitle()->prepend(__('Log'));

        return $resultPage;
    }
}
