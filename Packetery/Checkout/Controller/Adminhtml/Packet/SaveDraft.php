<?php

declare(strict_types=1);

namespace Packetery\Checkout\Controller\Adminhtml\Packet;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NotFoundException;
use Packetery\Checkout\Model\ResourceModel\Packetdraft\CollectionFactory;

class SaveDraft extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Packetery_Checkout::packetery';

    /**
     * Save constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Packetery\Checkout\Model\ResourceModel\PacketDraft\CollectionFactory $packetDraftCollectionFactory
     */
    public function __construct(
        Context $context,
        private readonly CollectionFactory $packetDraftCollectionFactory
    ) {
        parent::__construct($context);
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    private function getDataItem(array $data, string $key, $default)
    {
        if (!array_key_exists($key, $data)) {
            return $default;
        }

        return ($data[$key] ?: $default);
    }

    /**
     * @return Redirect
     * @throws \Exception
     */
    public function execute(): Redirect
    {
        if (!$this->getRequest()->isPost()) {
            throw new NotFoundException(__('Page not found'));
        }

        $postData = $this->getRequest()->getPostValue()['general'];
        $data = [
                'order_id'      => $postData['order_id'],
                'value'         => $postData['order_value'],
                'cod'           => $postData['cod_value'],
                'weight'        => $postData['weight'],
                'length'        => $postData['length'],
                'height'        => $postData['height'],
                'width'         => $postData['width'],
                'adult_content' => $postData['adult_content'],
                'dispatch_at'   => $postData['planned_dispatch'],
        ];

        $this->packetDraftCollectionFactory->saveData($data);

        $this->messageManager->addSuccessMessage(
            __('Saved')
        );

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('sales/order/view/order_id/' . $postData['magento_order_id']);
    }
}
