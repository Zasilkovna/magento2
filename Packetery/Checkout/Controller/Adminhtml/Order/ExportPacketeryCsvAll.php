<?php

declare(strict_types=1);

namespace Packetery\Checkout\Controller\Adminhtml\Order;

class ExportPacketeryCsvAll extends \Magento\Backend\App\Action
{
    /** @var \Magento\Framework\App\Response\Http\FileFactory */
    private $fileFactory;

    /** @var \Magento\Framework\View\Result\PageFactory */
    private $resultPageFactory;

    /** @var \Packetery\Checkout\Helper\Data */
    private $data;

    /** @var \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory */
    private $orderCollectionFactory;

    /**
     * ExportPacketeryCsvAll constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Packetery\Checkout\Helper\Data $data
     * @param \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context  $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Packetery\Checkout\Helper\Data $data,
        \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory
    ) {
        parent::__construct($context);

        $this->resultPageFactory  = $resultPageFactory;
        $this->data = $data;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->fileFactory = $fileFactory;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();

        $content = $resultPage->getLayout()->createBlock('Packetery\Checkout\Block\Adminhtml\Order\GridExport')->getCsvAllFileContents();
        if (!$content)
        {
            $this->messageManager->addError(__('Error! No export data found.'));
            $this->_redirect($this->_redirect->getRefererUrl());

            return;
        }

        $now = new \DateTime();

        /** @var \Packetery\Checkout\Model\ResourceModel\Order\Collection $collection */
        $collection = $this->orderCollectionFactory->create();
        $collection->setDataToAll(
            [
                'exported_at' => $now->format('Y-m-d H:i:s'),
                'exported' => 1
            ]
        );
        $collection->save();

        return $this->fileFactory->create(
            $this->data->getExportFileName(),
            $content,
            \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR
        );
    }
}
