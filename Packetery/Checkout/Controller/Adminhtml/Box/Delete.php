<?php

declare(strict_types=1);

namespace Packetery\Checkout\Controller\Adminhtml\Box;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Packetery\Checkout\Model\BoxRepository;
use Psr\Log\LoggerInterface as Logger;

class Delete extends Action
{
    public const ADMIN_RESOURCE = 'Packetery_Checkout::box';

    public function __construct(
        Context $context,
        private readonly BoxRepository $boxRepository,
        private readonly Logger $logger,
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface|ResponseInterface|Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        $id = (int) $this->getRequest()->getParam('id');
        if (!$id) {
            $this->messageManager->addErrorMessage(__('The item no longer exists.'));

            return $resultRedirect->setPath('*/*/');
        }

        try {
            $model = $this->boxRepository->getById($id);
            $this->boxRepository->deleteById($id);
            $this->messageManager->addSuccessMessage(__("Item '%1' was successfully deleted.", $model->getName()));

            return $resultRedirect->setPath('*/*/');
        } catch (NoSuchEntityException) {
            $this->messageManager->addErrorMessage(__('The item no longer exists.'));

            return $resultRedirect->setPath('*/*/');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Unable to delete the box.'));
            $this->logger->debug($e->getMessage());

            return $resultRedirect->setPath('*/*/detail', ['id' => $id]);
        }
    }
}
