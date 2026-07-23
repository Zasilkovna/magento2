<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Packet;

class BulkSubmitFeedback
{
    /**
     * @return array<int, array{type: string, text: \Magento\Framework\Phrase}>
     */
    public function build(BulkSubmitPlan $plan, int $queuedCount, int $failedCount): array
    {
        $messages = [];

        if ($queuedCount > 0) {
            $messages[] = [
                'type' => \Magento\Framework\Message\MessageInterface::TYPE_SUCCESS,
                'text' => __('Submission of %1 packet(s) was queued.', $queuedCount),
            ];

            if ($plan->getMissingConfigCount() > 0) {
                $messages[] = [
                    'type' => \Magento\Framework\Message\MessageInterface::TYPE_NOTICE,
                    'text' => __(
                        '%1 packet(s) not queued — API password and sender not configured (%2).',
                        $plan->getMissingConfigCount(),
                        implode(', ', $plan->getMissingConfigStoreNames())
                    ),
                ];
            }
        } else {
            $messages[] = [
                'type' => \Magento\Framework\Message\MessageInterface::TYPE_NOTICE,
                'text' => $this->buildNothingQueuedNotice($plan),
            ];
        }

        if ($failedCount > 0) {
            $messages[] = [
                'type' => \Magento\Framework\Message\MessageInterface::TYPE_ERROR,
                'text' => __('%1 packet(s) could not be queued for submission.', $failedCount),
            ];
        }

        return $messages;
    }

    private function buildNothingQueuedNotice(BulkSubmitPlan $plan): \Magento\Framework\Phrase
    {
        $alreadySubmitted = $plan->getAlreadySubmittedCount();
        $missingConfig = $plan->getMissingConfigCount();
        $stores = implode(', ', $plan->getMissingConfigStoreNames());

        if ($alreadySubmitted > 0 && $missingConfig > 0) {
            return __(
                'No packets were queued. %1 packet(s) already submitted; API password and sender not configured (%2).',
                $alreadySubmitted,
                $stores
            );
        }

        if ($alreadySubmitted > 0) {
            return __('No packets were queued. %1 packet(s) already submitted.', $alreadySubmitted);
        }

        if ($missingConfig > 0) {
            return __(
                'No packets were queued. %1 packet(s) not queued — API password and sender not configured (%2).',
                $missingConfig,
                $stores
            );
        }

        return __('No packets were queued.');
    }
}
