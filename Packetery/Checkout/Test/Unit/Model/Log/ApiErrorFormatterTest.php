<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit\Model\Log;

use Packetery\Checkout\Model\Log\ApiErrorFormatter;
use Packetery\Checkout\Test\BaseTest;
use PHPUnit\Framework\Attributes\DataProvider;

class ApiErrorFormatterTest extends BaseTest
{
    /**
     * @param string[] $soapDetailErrors
     */
    #[DataProvider('formatDataProvider')]
    public function testFormat(string $message, array $soapDetailErrors, string $expected): void
    {
        $formatter = new ApiErrorFormatter();

        $this->assertSame($expected, $formatter->format($message, $soapDetailErrors));
    }

    /**
     * @return array<string, array{message: string, soapDetailErrors: string[], expected: string}>
     */
    public static function formatDataProvider(): array
    {
        return [
            'message without details' => [
                'message' => 'Submission failed.',
                'soapDetailErrors' => [],
                'expected' => 'Submission failed.',
            ],
            'message with details' => [
                'message' => 'Submission failed.',
                'soapDetailErrors' => ['weight too high', 'invalid cod'],
                'expected' => 'Submission failed. (weight too high, invalid cod)',
            ],
            'empty message with details' => [
                'message' => '',
                'soapDetailErrors' => ['weight too high'],
                'expected' => 'weight too high',
            ],
        ];
    }
}
