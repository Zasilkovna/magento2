<?php

declare(strict_types=1);

namespace Packetery\Checkout\Controller\Adminhtml\PacketSettings;

class Index extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Packetery_Checkout::packetery';

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
        $resultPage->setActiveMenu('Packetery_Checkout::packetSettings');
        $resultPage->getConfig()->getTitle()->prepend(__('Nastavení zásilek'));

        return $resultPage;
    }
}
