<?php

declare(strict_types=1);

namespace Packetery\Checkout\Controller\Adminhtml\Box;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Packetery\Checkout\Model\BoxRepository;

class SetAsDefault extends Action
{
    public const ADMIN_RESOURCE = 'Packetery_Checkout::box';

    public function __construct(
        Context $context,
        private readonly BoxRepository $boxRepository,
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        $id = (int) $this->getRequest()->getParam('id');
        if (!$id) {
            $this->messageManager->addErrorMessage(__('The item no longer exists.'));

            return $resultRedirect->setPath('*/*/');
        }

        try {
            $box = $this->boxRepository->setAsDefault($id);
            $this->messageManager->addSuccessMessage(__("Box '%1' was set as default.", $box->getName()));
        } catch (NoSuchEntityException) {
            $this->messageManager->addErrorMessage(__('The item no longer exists.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $resultRedirect->setPath('*/*/');
    }
}
