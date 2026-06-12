<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit\Model\Log;

use Packetery\Checkout\Model\Log\RequestSanitizer;
use Packetery\Checkout\Test\BaseTest;
use PHPUnit\Framework\Attributes\DataProvider;

class RequestSanitizerTest extends BaseTest
{
    /**
     * @param array<string, scalar> $input
     * @param array<string, scalar> $expected
     */
    #[DataProvider('redactDataProvider')]
    public function testRedactRemovesSensitiveValues(array $input, array $expected): void
    {
        $sanitizer = new RequestSanitizer();

        $this->assertSame($expected, $sanitizer->redact($input));
    }

    /**
     * @return array<string, array{input: array<string, scalar>, expected: array<string, scalar>}>
     */
    public static function redactDataProvider(): array
    {
        return [
            'plain api_password key is redacted' => [
                'input' => ['number' => 'Z1', 'api_password' => 'topsecret', 'weight' => 2],
                'expected' => ['number' => 'Z1', 'api_password' => '***', 'weight' => 2],
            ],
            'camelCase apiPassword key is redacted' => [
                'input' => ['apiPassword' => 'topsecret', 'eshop' => 'demo'],
                'expected' => ['apiPassword' => '***', 'eshop' => 'demo'],
            ],
            'password and secret keys are redacted' => [
                'input' => ['password' => 'p', 'clientSecret' => 's', 'name' => 'John'],
                'expected' => ['password' => '***', 'clientSecret' => '***', 'name' => 'John'],
            ],
            'no sensitive keys keeps data intact' => [
                'input' => ['number' => 'Z1', 'value' => 100],
                'expected' => ['number' => 'Z1', 'value' => 100],
            ],
        ];
    }

    public function testRedactToJsonProducesJsonWithoutSecret(): void
    {
        $sanitizer = new RequestSanitizer();

        $json = $sanitizer->redactToJson(['number' => 'Z1', 'api_password' => 'topsecret']);

        $this->assertStringNotContainsString('topsecret', $json);
        $this->assertStringContainsString('***', $json);
        $this->assertSame(
            ['number' => 'Z1', 'api_password' => '***'],
            json_decode($json, true)
        );
    }
}
