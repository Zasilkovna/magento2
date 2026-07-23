<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit\Model\Packet;

use Magento\Framework\Message\MessageInterface;
use Packetery\Checkout\Model\Packet\BulkSubmitFeedback;
use Packetery\Checkout\Model\Packet\BulkSubmitPlan;
use Packetery\Checkout\Test\BaseTest;
use PHPUnit\Framework\Attributes\DataProvider;

class BulkSubmitFeedbackTest extends BaseTest
{
    /** @return array<string, array{BulkSubmitPlan, int, int, array<int, array{type: string, text: string, args: array<int, mixed>}>}> */
    public static function feedbackProvider(): array
    {
        return [
            'queued only — already submitted is not mentioned' => [
                new BulkSubmitPlan([1, 2], 3, 0, []),
                2,
                0,
                [
                    ['type' => MessageInterface::TYPE_SUCCESS, 'text' => 'Submission of %1 packet(s) was queued.', 'args' => [2]],
                ],
            ],
            'queued plus missing configuration' => [
                new BulkSubmitPlan([1], 0, 5, ['Store B']),
                1,
                0,
                [
                    ['type' => MessageInterface::TYPE_SUCCESS, 'text' => 'Submission of %1 packet(s) was queued.', 'args' => [1]],
                    ['type' => MessageInterface::TYPE_NOTICE, 'text' => '%1 packet(s) not queued — API password and sender not configured (%2).', 'args' => [5, 'Store B']],
                ],
            ],
            'queued plus technical failure' => [
                new BulkSubmitPlan([1, 2, 3], 0, 0, []),
                3,
                2,
                [
                    ['type' => MessageInterface::TYPE_SUCCESS, 'text' => 'Submission of %1 packet(s) was queued.', 'args' => [3]],
                    ['type' => MessageInterface::TYPE_ERROR, 'text' => '%1 packet(s) could not be queued for submission.', 'args' => [2]],
                ],
            ],
            'nothing queued — already submitted only' => [
                new BulkSubmitPlan([], 3, 0, []),
                0,
                0,
                [
                    ['type' => MessageInterface::TYPE_NOTICE, 'text' => 'No packets were queued. %1 packet(s) already submitted.', 'args' => [3]],
                ],
            ],
            'nothing queued — missing configuration only' => [
                new BulkSubmitPlan([], 0, 5, ['Store B', 'Store C']),
                0,
                0,
                [
                    ['type' => MessageInterface::TYPE_NOTICE, 'text' => 'No packets were queued. %1 packet(s) not queued — API password and sender not configured (%2).', 'args' => [5, 'Store B, Store C']],
                ],
            ],
            'nothing queued — both reasons in a single notice' => [
                new BulkSubmitPlan([], 3, 2, ['Store B']),
                0,
                0,
                [
                    ['type' => MessageInterface::TYPE_NOTICE, 'text' => 'No packets were queued. %1 packet(s) already submitted; API password and sender not configured (%2).', 'args' => [3, 'Store B']],
                ],
            ],
            'nothing queued — only technical failures' => [
                new BulkSubmitPlan([1, 2], 0, 0, []),
                0,
                2,
                [
                    ['type' => MessageInterface::TYPE_NOTICE, 'text' => 'No packets were queued.', 'args' => []],
                    ['type' => MessageInterface::TYPE_ERROR, 'text' => '%1 packet(s) could not be queued for submission.', 'args' => [2]],
                ],
            ],
        ];
    }

    /**
     * @param array<int, array{type: string, text: string, args: array<int, mixed>}> $expected
     */
    #[DataProvider('feedbackProvider')]
    public function testBuild(BulkSubmitPlan $plan, int $queuedCount, int $failedCount, array $expected): void
    {
        $messages = (new BulkSubmitFeedback())->build($plan, $queuedCount, $failedCount);

        $actual = array_map(
            static fn(array $message): array => [
                'type' => $message['type'],
                'text' => $message['text']->getText(),
                'args' => $message['text']->getArguments(),
            ],
            $messages
        );

        $this->assertSame($expected, $actual);
    }
}
