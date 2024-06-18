<?php

declare(strict_types=1);

namespace Packetery\Checkout\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;

class InlineEdit extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Packetery_Checkout::packetery';

    private \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory;

    /**
     * Save constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     */
    public function __construct(
        Context $context,
        \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $postItems = $this->getRequest()->getParam('items', []);
        foreach ($postItems as $modelId => $postItem) {
            foreach ($postItem as $column => $value) {
                if (is_numeric($value)) {
                    $postItem[$column] = str_replace(',', '.', $value);
                }
            }

            $orderCollection = $this->orderCollectionFactory->create();
            $orderCollection->addFilter($orderCollection->getIdFieldName(), $modelId);
            $orderCollection->setDataToAll($postItem);
            $orderCollection->save();
        }

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData([]);
    }
}
