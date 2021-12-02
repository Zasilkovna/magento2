<?php

declare(strict_types=1);

namespace Packetery\Checkout\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NotFoundException;
use Packetery\Checkout\Model\Carrier\Methods;

class Save extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Packetery_Checkout::packetery';

    /** @var \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory */
    private $orderCollectionFactory;

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

    /**
     * @return Redirect
     */
    public function execute(): Redirect
    {
        if (!$this->getRequest()->isPost()) {
            throw new NotFoundException(__('Page not found'));
        }

        $postData = $this->getRequest()->getPostValue()['general'];
        $id = $postData['id'];
        $carrierPickupPoint = ($postData['carrier_pickup_point'] ?? null);
        $misc = $postData['misc'];

        $collection = $this->orderCollectionFactory->create();
        $collection->addFilter('id', $id);

        if (Methods::isAnyAddressDelivery($misc['method'])) {
            $collection->setDataToAll(
                [
                    'address_validated' => true,
                    'recipient_street' => ($postData['recipient_street'] ?? null),
                    'recipient_house_number' => ($postData['recipient_house_number'] ?? null),
                    'recipient_country_id' => $postData['recipient_country_id'],
                    'recipient_county' => ($postData['recipient_county'] ?? null),
                    'recipient_city' => ($postData['recipient_city'] ?? null),
                    'recipient_zip' => ($postData['recipient_zip'] ?? null),
                    'recipient_longitude' => ($postData['recipient_longitude'] ?? null),
                    'recipient_latitude' => ($postData['recipient_latitude'] ?? null),
                ]
            );
        }

        if (Methods::isPickupPointDelivery($misc['method'])) {
            $collection->setDataToAll(
                [
                    'point_id' => $postData['point_id'],
                    'point_name' => $postData['point_name'],
                    'is_carrier' => (bool)$postData['is_carrier'],
                    'carrier_pickup_point' => ($carrierPickupPoint ?: null),
                ]
            );
        }

        $collection->save();

        $this->messageManager->addSuccessMessage(
            __('Saved')
        );

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('packetery/order/detail/id/' . $id);
    }
}
