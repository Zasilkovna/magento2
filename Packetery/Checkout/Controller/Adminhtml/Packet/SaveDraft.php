<?php

declare(strict_types=1);

namespace Packetery\Checkout\Controller\Adminhtml\Packet;

use Laminas\Http\Request;
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
     * @param Context $context
     * @param \Packetery\Checkout\Model\ResourceModel\PacketDraft\CollectionFactory $packetDraftCollectionFactory
     */
    public function __construct(
        Context $context,
        private readonly CollectionFactory $packetDraftCollectionFactory
    ) {
        parent::__construct($context);
    }

    /**
     * @return Redirect
     * @throws \Exception
     */
    public function execute(): Redirect
    {
        /** @var Request $request */
        $request = $this->getRequest();
        if (!$request->isPost()) {
            throw new NotFoundException(__('Page not found'));
        }

        $postData = $request->getPost('general');
        $data = [
                'order_id'      => $postData['order_id'],
                'value'         => $postData['order_value']   === '' ? null : $postData['order_value'],
                'cod'           => $postData['cod_value']     === '' ? null : $postData['cod_value'],
                'weight'        => $postData['weight']        === '' ? null : $postData['weight'],
                'length'        => $postData['length']        === '' ? null : $postData['length'],
                'height'        => $postData['height']        === '' ? null : $postData['height'],
                'width'         => $postData['width']         === '' ? null : $postData['width'],
                'adult_content' => $postData['adult_content'] === '' ? null : $postData['adult_content'],
                'dispatch_at'   => $postData['dispatch_at']   === '' ? null : $postData['dispatch_at'],
        ];

        $this->packetDraftCollectionFactory->saveData($data);

        $this->messageManager->addSuccessMessage(
            __('Saved')
        );

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('sales/order/view/order_id/' . $postData['magento_order_id']);
    }
}
