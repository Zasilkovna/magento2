<?php

namespace Packetery\Checkout\Controller\Adminhtml\Order;

class Index extends \Magento\Backend\App\Action
{
    protected $resultPageFactory = false;

    /** @var \Packetery\Checkout\Model\FeatureFlag\Manager */
    private $featureFlagManager;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Packetery\Checkout\Model\FeatureFlag\Manager $featureFlagManager
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->featureFlagManager = $featureFlagManager;
    }

    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Packetery_Checkout::orders');
        $resultPage->getConfig()->getTitle()->prepend(__('Orders'));

        $this->featureFlagManager->isSplitActive();

        return $resultPage;
    }
}
