<?php

declare(strict_types=1);

namespace Packetery\Checkout\Controller\Adminhtml\AutoSubmit;

class Index extends \Magento\Backend\App\Action
{
    /** @var \Magento\Framework\View\Result\PageFactory */
    private $resultPageFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute(): \Magento\Framework\View\Result\Page
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Packetery_Checkout::autoSubmit');
        $resultPage->getConfig()->getTitle()->prepend(__('Payment method to order status mapping'));

        return $resultPage;
    }
}
