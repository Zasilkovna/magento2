<?php
namespace Packetery\Checkout\Controller\Adminhtml\Order;

class ExportPacketeryCsv extends \Magento\Backend\App\Action
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

    /** @var \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory */
    private $orderCollectionFactory;

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
        $this->_fileFactory = $fileFactory;
    }
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();

        $content = $resultPage->getLayout()->createBlock('Packetery\Checkout\Block\Adminhtml\Order\GridExport')->getCsvAllFileContents(TRUE);
        if (!$content)
        {
            $this->messageManager->addError(__('Error! No export data found.'));
            $this->_redirect($this->_redirect->getRefererUrl());

            return;
        }


        $now = new \DateTime();

        /** @var \Packetery\Checkout\Model\ResourceModel\Order\Collection $collection */
        $collection = $this->orderCollectionFactory->create();
        $collection->addFieldToFilter('exported', ['neq' => 1]);
        $collection->setDataToAll(
            [
                'exported_at' => $now->format('Y-m-d H:i:s'),
                'exported' => 1
            ]
        );
        $collection->save();

        return $this->_fileFactory->create(
            $this->data->getExportFileName(),
            $content,
            \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR
        );
    }
}
