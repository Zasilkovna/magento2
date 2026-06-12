<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit\Dimensions;

use Packetery\Checkout\Model\Dimensions\Converter;
use Packetery\Checkout\Model\Dimensions\Unit;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ConverterTest extends TestCase
{
    #[DataProvider('cmToMmProvider')]
    public function testConvertCmToMm(float $cm, float $expected): void
    {
        $this->assertSame($expected, (new Converter())->convert($cm, Unit::CM, Unit::MM));
    }

    /**
     * @return array<string, array{float, float}>
     */
    public static function cmToMmProvider(): array
    {
        return [
            'zero' => [0.0, 0.0],
            'whole cm' => [30.0, 300.0],
            'half cm' => [15.5, 155.0],
            'negative passes through' => [-10.0, -100.0],
        ];
    }

    #[DataProvider('formatCmProvider')]
    public function testFormatCm(float $cm, string $expected): void
    {
        $this->assertSame($expected, (new Converter())->formatCm($cm));
    }

    /**
     * @return array<string, array{float, string}>
     */
    public static function formatCmProvider(): array
    {
        return [
            'zero' => [0.0, '0'],
            'whole number drops decimals' => [30.0, '30'],
            'keeps one decimal' => [30.5, '30.5'],
            'trims trailing zero' => [20.0, '20'],
        ];
    }

    public function testConvertMmToCmKeepsFraction(): void
    {
        $this->assertSame(30.5, (new Converter())->convert(305.0, Unit::MM, Unit::CM));
    }

    public function testRenderLabel(): void
    {
        $this->assertSame(
            'M (30 × 20 × 10 cm)',
            (new Converter())->label('M', 30.0, 20.0, 10.0)
        );
    }
}
