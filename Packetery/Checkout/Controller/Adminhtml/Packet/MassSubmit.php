<?php

declare(strict_types=1);

namespace Packetery\Checkout\Controller\Adminhtml\Packet;

use Magento\Backend\App\Action;
use Magento\Framework\Message\MessageInterface;
use Packetery\Checkout\Logger\BulkPacketSubmitLogger;
use Packetery\Checkout\Model\Export\ConvertToCsvCustom;
use Packetery\Checkout\Model\Packet\BulkPacketSubmitPublisher;
use Packetery\Checkout\Model\Packet\BulkSubmitFeedback;
use Packetery\Checkout\Model\Packet\BulkSubmitPlanner;

class MassSubmit extends Action
{
    public const ADMIN_RESOURCE = 'Packetery_Checkout::packetery';

    /** @var ConvertToCsvCustom */
    private $converter;

    /** @var BulkSubmitPlanner */
    private $bulkSubmitPlanner;

    /** @var BulkPacketSubmitPublisher */
    private $bulkPacketSubmitPublisher;

    /** @var BulkSubmitFeedback */
    private $bulkSubmitFeedback;

    /** @var BulkPacketSubmitLogger */
    private $logger;

    public function __construct(
        Action\Context $context,
        ConvertToCsvCustom $converter,
        BulkSubmitPlanner $bulkSubmitPlanner,
        BulkPacketSubmitPublisher $bulkPacketSubmitPublisher,
        BulkSubmitFeedback $bulkSubmitFeedback,
        BulkPacketSubmitLogger $logger
    ) {
        parent::__construct($context);
        $this->converter = $converter;
        $this->bulkSubmitPlanner = $bulkSubmitPlanner;
        $this->bulkPacketSubmitPublisher = $bulkPacketSubmitPublisher;
        $this->bulkSubmitFeedback = $bulkSubmitFeedback;
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

        $plan = $this->bulkSubmitPlanner->plan(array_map('intval', $orderIds));

        $queuedCount = 0;
        $failedCount = 0;
        foreach ($plan->getQueueableOrderIds() as $orderId) {
            try {
                $this->bulkPacketSubmitPublisher->publish($orderId);
                $queuedCount++;
            } catch (\Exception $exception) {
                $failedCount++;
                $this->logger->error(
                    'Packet mass submit publish failed.',
                    [
                        'packetery_order_id' => $orderId,
                        'exception' => $exception,
                    ]
                );
            }
        }

        foreach ($this->bulkSubmitFeedback->build($plan, $queuedCount, $failedCount) as $message) {
            $this->addMessage($message['type'], $message['text']);
        }

        return $resultRedirect;
    }

    private function addMessage(string $type, \Magento\Framework\Phrase $text): void
    {
        if ($type === MessageInterface::TYPE_SUCCESS) {
            $this->messageManager->addSuccessMessage($text);
            return;
        }

        if ($type === MessageInterface::TYPE_ERROR) {
            $this->messageManager->addErrorMessage($text);
            return;
        }

        $this->messageManager->addNoticeMessage($text);
    }
}
