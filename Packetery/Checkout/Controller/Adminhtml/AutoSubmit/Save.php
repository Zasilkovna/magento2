<?php

declare(strict_types=1);

namespace Packetery\Checkout\Controller\Adminhtml\AutoSubmit;

class Save extends \Magento\Backend\App\Action
{
    /** @var \Magento\Framework\App\Config\Storage\WriterInterface */
    private $configWriter;

    /** @var \Magento\Framework\App\Cache\TypeListInterface */
    private $cacheTypeList;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
    ) {
        parent::__construct($context);
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
    }

    public function execute(): \Magento\Framework\Controller\Result\Redirect
    {
        $rows = [];
        foreach ($this->getRequest()->getPost('mapping', []) as $paymentMethod => $orderStatus) {
            if ($orderStatus !== '') {
                $rows[] = [
                    'payment_method' => $paymentMethod,
                    'order_status' => $orderStatus,
                ];
            }
        }

        $this->configWriter->save('carriers/packetery/auto_submit_status_map', json_encode($rows));
        $this->cacheTypeList->invalidate(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
        $this->messageManager->addSuccessMessage(__('Mapping has been saved.'));

        return $this->resultRedirectFactory->create()->setPath('*/*/index');
    }
}
