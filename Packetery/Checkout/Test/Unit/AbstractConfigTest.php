<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit;

use Packetery\Checkout\Model\Carrier\Config\AbstractConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AbstractConfigTest extends TestCase
{
    /**
     * @param array<string, mixed>|null $data
     */
    #[DataProvider('defaultWeightProvider')]
    public function testGetDefaultWeight(?array $data, ?float $expected): void
    {
        $this->assertSame(
            $expected,
            $this->createConfig($data)->getDefaultWeight()
        );
    }

    /**
     * @return array<string, array{array<string, mixed>|null, ?float}>
     */
    public static function defaultWeightProvider(): array
    {
        return [
            'null data' => [null, null],
            'missing key' => [['something_else' => '1'], null],
            'empty string' => [['default_weight' => ''], null],
            'non-numeric' => [['default_weight' => 'abc'], null],
            'zero string' => [['default_weight' => '0'], 0.0],
            'decimal string' => [['default_weight' => '2.5'], 2.5],
            'float value' => [['default_weight' => 1.25], 1.25],
        ];
    }

    /**
     * @param array<string, mixed>|null $data
     */
    private function createConfig(?array $data): AbstractConfig
    {
        return new class($data) extends AbstractConfig {
            protected function normalizeLabelFormatValue(string $value): string
            {
                return $value;
            }
        };
    }
}
