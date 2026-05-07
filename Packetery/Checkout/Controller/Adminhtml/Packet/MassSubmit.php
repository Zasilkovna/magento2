<?php

declare(strict_types=1);

namespace Packetery\Checkout\Controller\Adminhtml\Packet;

use Magento\Backend\App\Action;
use Packetery\Checkout\Logger\BulkPacketSubmitLogger;
use Packetery\Checkout\Model\Export\ConvertToCsvCustom;
use Packetery\Checkout\Model\Packet\BulkPacketSubmitPublisher;

class MassSubmit extends Action
{
    public const ADMIN_RESOURCE = 'Packetery_Checkout::packetery';

    /** @var ConvertToCsvCustom */
    private $converter;

    /** @var BulkPacketSubmitPublisher */
    private $bulkPacketSubmitPublisher;

    /** @var BulkPacketSubmitLogger */
    private $logger;

    public function __construct(
        Action\Context $context,
        ConvertToCsvCustom $converter,
        BulkPacketSubmitPublisher $bulkPacketSubmitPublisher,
        BulkPacketSubmitLogger $logger
    ) {
        parent::__construct($context);
        $this->converter = $converter;
        $this->bulkPacketSubmitPublisher = $bulkPacketSubmitPublisher;
        $this->logger = $logger;
    }

    public function execute(): \Magento\Framework\Controller\Result\Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create()->setPath('packetery/order/index');
        $selected = $this->getRequest()->getParam('selected');
        if ($selected === 'false') {
            $this->messageManager->addErrorMessage(__('No packets selected.'));
            return $resultRedirect;
        }

        $orderIds = $this->converter->getItemIds();
        if ($orderIds === []) {
            $this->messageManager->addErrorMessage(__('No packets selected.'));
            return $resultRedirect;
        }

        $publishedCount = 0;
        $failedCount = 0;
        foreach ($orderIds as $orderId) {
            try {
                $this->bulkPacketSubmitPublisher->publish((int) $orderId);
                $publishedCount++;
            } catch (\Exception $exception) {
                $failedCount++;
                $this->logger->error(
                    'Packet mass submit publish failed.',
                    [
                        'packetery_order_id' => (int) $orderId,
                        'exception' => $exception,
                    ]
                );
            }
        }

        if ($publishedCount > 0) {
            $this->messageManager->addSuccessMessage(
                __('Submission of %1 packet(s) was queued.', $publishedCount)
            );
        }

        if ($failedCount > 0) {
            $this->messageManager->addErrorMessage(
                __('%1 packet(s) could not be queued for submission.', $failedCount)
            );
        }

        return $resultRedirect;
    }
}

