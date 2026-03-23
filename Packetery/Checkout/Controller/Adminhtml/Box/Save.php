<?php

declare(strict_types=1);

namespace Packetery\Checkout\Controller\Adminhtml\Box;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Packetery\Checkout\Model\BoxFactory;
use Packetery\Checkout\Model\BoxRepository;

class Save extends Action
{
    public const ADMIN_RESOURCE = 'Packetery_Checkout::box';

    public function __construct(
        Context $context,
        private readonly BoxFactory $boxFactory,
        private readonly BoxRepository $boxRepository,
        private readonly DataPersistorInterface $dataPersistor
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        $data = $this->getRequest()->getPostValue();
        if ($data) {
            $id = (int) $this->getRequest()->getParam('id');

            try {
                if ($id) {
                    $model = $this->boxRepository->getById($id);
                } else {
                    $model = $this->boxFactory->create();
                }

                unset($data['id']);
                $model->addData($data);

                $this->boxRepository->save($model);
                $this->messageManager->addSuccessMessage(__('Saved'));
                $this->dataPersistor->clear('packetery_box');

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/detail', ['id' => $model->getId()]);
                }

                return $resultRedirect->setPath('*/*/');
            } catch (NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(__('The item no longer exists.'));
                return $resultRedirect->setPath('*/*/');
            } catch (CouldNotSaveException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->dataPersistor->set('packetery_box', $data);
                return $resultRedirect->setPath('*/*/detail', ['id' => $id]);
            }
        }

        return $resultRedirect->setPath('*/*/');
    }
}
