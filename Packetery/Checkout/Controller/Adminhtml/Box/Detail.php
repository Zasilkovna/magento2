<?php

declare(strict_types=1);

namespace Packetery\Checkout\Controller\Adminhtml\Box;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;
use Packetery\Checkout\Model\BoxFactory;
use Packetery\Checkout\Model\BoxRepository;

class Detail extends Action
{
    public const ADMIN_RESOURCE = 'Packetery_Checkout::box';

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly BoxFactory $boxFactory,
        private readonly BoxRepository $boxRepository,
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $id = (int) $this->getRequest()->getParam('id');
        if ($id) {
            try {
                $model = $this->boxRepository->getById($id);
            } catch (NoSuchEntityException) {
                $this->messageManager->addErrorMessage(__('Item does not exists enymore'));

                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        } else {
            $model = $this->boxFactory->create();
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(
            $model->getId()
                ? __('Edit') . ': ' . $model->getName()
                : __('Add Box')
        );

        return $resultPage;
    }
}
