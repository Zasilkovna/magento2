<?php

declare(strict_types=1);

namespace Packetery\Checkout\Controller\Adminhtml\Packet;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Phrase;
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

    /** @var JsonFactory */
    private $resultJsonFactory;

    /** @var PacketeryOrderCollectionFactory */
    private $packeteryOrderCollectionFactory;

    /** @var OrderFactory */
    private $magentoOrderFactory;

    /** @var PacketLabelPrinter */
    private $packetLabelPrinter;

    /** @var \Packetery\Checkout\Model\Log\LogWriter */
    private $logWriter;

    /** @var \Packetery\Checkout\Model\Log\ApiErrorFormatter */
    private $apiErrorFormatter;

    public function __construct(
        Context $context,
        RawFactory $resultRawFactory,
        JsonFactory $resultJsonFactory,
        PacketeryOrderCollectionFactory $packeteryOrderCollectionFactory,
        OrderFactory $magentoOrderFactory,
        PacketLabelPrinter $packetLabelPrinter,
        \Packetery\Checkout\Model\Log\LogWriter $logWriter,
        \Packetery\Checkout\Model\Log\ApiErrorFormatter $apiErrorFormatter
    ) {
        parent::__construct($context);
        $this->resultRawFactory = $resultRawFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->packeteryOrderCollectionFactory = $packeteryOrderCollectionFactory;
        $this->magentoOrderFactory = $magentoOrderFactory;
        $this->packetLabelPrinter = $packetLabelPrinter;
        $this->logWriter = $logWriter;
        $this->apiErrorFormatter = $apiErrorFormatter;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Raw|ResultInterface
     */
    public function execute()
    {
        $orderId = (int) $this->getRequest()->getParam('order_id');
        if ($orderId <= 0) {
            return $this->createErrorResult(__('Order not found'));
        }

        $collection = $this->packeteryOrderCollectionFactory->create();
        $collection->addFieldToFilter('id', $orderId);
        $packeteryOrder = $collection->getFirstItem();
        if (!$packeteryOrder->getId()) {
            return $this->createErrorResult(__('Order not found'));
        }

        $magentoOrder = $this->magentoOrderFactory->create()->loadByIncrementId($packeteryOrder->getOrderNumber());
        if (!$magentoOrder->getId()) {
            return $this->createErrorResult(
                __('Order %1 not found.', $packeteryOrder->getOrderNumber())
            );
        }

        $offset = (int) $this->getRequest()->getParam('offset');

        try {
            $contents = $this->packetLabelPrinter->printLabelPdf($packeteryOrder, $magentoOrder, $offset);
        } catch (PacketLabelException $exception) {
            $errors = $exception->getSoapDetailErrors();
            $firstMessage = $errors[0] ?? $exception->getMessage();

            $this->logWriter->logError(
                \Packetery\Checkout\Model\Log::ACTION_PRINT_LABEL,
                $packeteryOrder->getOrderNumber(),
                ['orderNumber' => $packeteryOrder->getOrderNumber()],
                $this->apiErrorFormatter->format($exception->getMessage(), $errors)
            );

            return $this->createErrorResult(
                new ComboPhrase(
                    [
                        __('The label could not be generated.'),
                        ' ',
                        $firstMessage,
                    ]
                )
            );
        } catch (PacketLabelLocalizedException $exception) {
            return $this->createErrorResult($exception->getMessage());
        }

        $this->logWriter->logSuccess(
            \Packetery\Checkout\Model\Log::ACTION_PRINT_LABEL,
            $packeteryOrder->getOrderNumber(),
            __('Printed label for order %1.', $packeteryOrder->getOrderNumber())
        );

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

    /**
     * For the modal (AJAX) flow the message must stay out of the session,
     * otherwise it would pop up on an unrelated page load later.
     *
     * @param Phrase|string $message
     */
    private function createErrorResult($message): ResultInterface
    {
        $request = $this->getRequest();
        if ($request instanceof \Magento\Framework\App\Request\Http && $request->isXmlHttpRequest()) {
            return $this->resultJsonFactory->create()->setData(
                [
                    'message' => (string) $message,
                ]
            );
        }

        $this->messageManager->addErrorMessage($message);

        return $this->resultRedirectFactory->create()->setPath('packetery/order/index');
    }
}
