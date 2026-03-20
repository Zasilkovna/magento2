<?php

declare(strict_types=1);

namespace Packetery\Checkout\Controller\Adminhtml\Packet;

use Magento\Framework\Controller\Result\Redirect;

class PrintLabelForm extends \Magento\Backend\App\Action
{
    public const ADMIN_RESOURCE = 'Packetery_Checkout::packetery';

    /** @var \Magento\Framework\View\Result\PageFactory */
    private $resultPageFactory;

    /** @var \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory */
    private $packeteryOrderCollectionFactory;

    /** @var \Magento\Sales\Model\OrderFactory */
    private $magentoOrderFactory;

    /** @var \Magento\Shipping\Model\CarrierFactory */
    private $carrierFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $packeteryOrderCollectionFactory,
        \Magento\Sales\Model\OrderFactory $magentoOrderFactory,
        \Magento\Shipping\Model\CarrierFactory $carrierFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->packeteryOrderCollectionFactory = $packeteryOrderCollectionFactory;
        $this->magentoOrderFactory = $magentoOrderFactory;
        $this->carrierFactory = $carrierFactory;
    }

    /**
     * @return \Magento\Framework\View\Result\Page|Redirect
     */
    public function execute()
    {
        $orderId = (int) $this->getRequest()->getParam('order_id');
        $resultRedirect = $this->resultRedirectFactory->create()->setPath('packetery/order/index');
        if ($orderId <= 0) {
            $this->messageManager->addErrorMessage(__('Page not found'));
            return $resultRedirect;
        }

        $collection = $this->packeteryOrderCollectionFactory->create();
        $collection->addFieldToFilter('id', $orderId);
        $packeteryOrder = $collection->getFirstItem();
        if (!$packeteryOrder->getId()) {
            $this->messageManager->addErrorMessage(__('Page not found'));
            return $resultRedirect;
        }

        $magentoOrder = $this->magentoOrderFactory->create()->loadByIncrementId($packeteryOrder->getOrderNumber());
        if (!$magentoOrder->getId()) {
            $this->messageManager->addErrorMessage(
                __('Order %1 not found.', $packeteryOrder->getOrderNumber())
            );
            return $resultRedirect;
        }

        $storeId = (int) $magentoOrder->getStoreId();
        $shippingMethod = (string) $magentoOrder->getShippingMethod();
        if (\Packetery\Checkout\Model\Carrier\ShippingRateCode::isPacketery($shippingMethod) === false) {
            $this->messageManager->addErrorMessage(__('Label format is not configured.'));

            return $resultRedirect;
        }

        $shippingRateCode = \Packetery\Checkout\Model\Carrier\ShippingRateCode::fromString($shippingMethod);
        $carrierCode = $shippingRateCode->getCarrierCode();
        $carrier = $this->carrierFactory->create($carrierCode, $storeId);
        if (!$carrier instanceof \Magento\Shipping\Model\Carrier\AbstractCarrier) {
            $this->messageManager->addErrorMessage(__('Label format is not configured.'));

            return $resultRedirect;
        }

        $format = $carrier->getPacketeryConfig()->getLabelFormat();
        $maxOffset = \Packetery\Checkout\Model\Label\LabelFormats::getMaxOffset($format);
        if ($maxOffset === 0) {
            return $this->resultRedirectFactory->create()->setPath(
                'packetery/packet/printlabel',
                ['order_id' => $orderId, 'offset' => 0]
            );
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Packetery_Checkout::orders');
        $resultPage->getConfig()->getTitle()->prepend(__('Print label'));

        return $resultPage;
    }
}
