<?php

declare(strict_types=1);

namespace Packetery\Checkout\Controller\Adminhtml\Packet;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Sales\Model\OrderFactory;
use Packetery\Checkout\Model\Api\PacketLabelException;
use Packetery\Checkout\Model\Misc\ComboPhrase;
use Packetery\Checkout\Model\Packet\PacketLabelPrinter;
use Packetery\Checkout\Model\Packet\PacketLabelLocalizedException;
use Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory as PacketeryOrderCollectionFactory;

class PrintLabel extends Action
{
    public const ADMIN_RESOURCE = 'Packetery_Checkout::packetery';

    /** @var RawFactory */
    private $resultRawFactory;

    /** @var PacketeryOrderCollectionFactory */
    private $packeteryOrderCollectionFactory;

    /** @var OrderFactory */
    private $magentoOrderFactory;

    /** @var PacketLabelPrinter */
    private $packetLabelPrinter;

    public function __construct(
        Context $context,
        RawFactory $resultRawFactory,
        PacketeryOrderCollectionFactory $packeteryOrderCollectionFactory,
        OrderFactory $magentoOrderFactory,
        PacketLabelPrinter $packetLabelPrinter
    ) {
        parent::__construct($context);
        $this->resultRawFactory = $resultRawFactory;
        $this->packeteryOrderCollectionFactory = $packeteryOrderCollectionFactory;
        $this->magentoOrderFactory = $magentoOrderFactory;
        $this->packetLabelPrinter = $packetLabelPrinter;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Raw|Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create()->setPath('packetery/order/index');
        $orderId = (int) $this->getRequest()->getParam('order_id');
        if ($orderId <= 0) {
            $this->messageManager->addErrorMessage(__('Order not found'));
            return $resultRedirect;
        }

        $collection = $this->packeteryOrderCollectionFactory->create();
        $collection->addFieldToFilter('id', $orderId);
        $packeteryOrder = $collection->getFirstItem();
        if (!$packeteryOrder->getId()) {
            $this->messageManager->addErrorMessage(__('Order not found'));
            return $resultRedirect;
        }

        $magentoOrder = $this->magentoOrderFactory->create()->loadByIncrementId($packeteryOrder->getOrderNumber());
        if (!$magentoOrder->getId()) {
            $this->messageManager->addErrorMessage(
                __('Order %1 not found.', $packeteryOrder->getOrderNumber())
            );
            return $resultRedirect;
        }

        $offset = (int) $this->getRequest()->getParam('offset');

        try {
            $contents = $this->packetLabelPrinter->printLabelPdf($packeteryOrder, $magentoOrder, $offset);
        } catch (PacketLabelException $exception) {
            $errors = $exception->getSoapDetailErrors();
            $firstMessage = $errors[0] ?? $exception->getMessage();

            $this->messageManager->addErrorMessage(
                new ComboPhrase(
                    [
                        __('The label could not be generated.'),
                        ' ',
                        $firstMessage,
                    ]
                )
            );

            return $resultRedirect;
        } catch (PacketLabelLocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
            return $resultRedirect;
        }

        $raw = $this->resultRawFactory->create();
        $raw->setHeader('Content-Type', 'application/pdf', true);
        $raw->setHeader('Content-Transfer-Encoding', 'binary', true);
        $raw->setHeader('Content-Length', (string) strlen($contents), true);
        $raw->setHeader(
            'Content-Disposition',
            'inline; filename="packeta_label.pdf"',
            true
        );
        $raw->setContents($contents);

        return $raw;
    }
}
