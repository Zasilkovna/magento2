<?php
namespace Packetery\Checkout\Controller\Adminhtml\Order;

class GeneratePdfAll extends \Magento\Backend\App\Action
{

    protected $_fileFactory;
    protected $_response;
    protected $_view;
    protected $directory;
    protected $converter;
    protected $resultPageFactory;
    protected $directory_list;

    /** @var \Packetery\Checkout\Helper\Data */
    private $data;

    public function __construct(
        \Magento\Backend\App\Action\Context  $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Packetery\Checkout\Helper\Data $data
    ) {
        parent::__construct($context);

        $this->resultPageFactory  = $resultPageFactory;
        $this->data = $data;
    }
    public function execute()
    {
        $orderIds = $this->getRequest()->getParam('internal_order_id');

        if (empty($orderIds))
        {
            $this->messageManager->addError(__('No orders to export.'));
            $this->_redirect($this->_redirect->getRefererUrl());

            return;
        }
        $resultPage = $this->resultPageFactory->create();
        $content = $resultPage->getLayout()->createBlock('Packetery\Checkout\Block\Adminhtml\Order\GridExport')->getPdf($orderIds);
       return;

    }

    protected function _sendUploadResponse($fileName, $content, $contentType='application/octet-stream')
    {
        $this->_response->setHttpResponseCode(200)
            ->setHeader('Pragma', 'public', true)
            ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
            ->setHeader('Content-type', $contentType, true)
            ->setHeader('Content-Length', strlen($content), true)
            ->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"', true)
            ->setHeader('Last-Modified', date('r'), true)
            ->setBody($content)
            ->sendResponse();
        die;
    }
}
