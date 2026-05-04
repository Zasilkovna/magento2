<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit;

use Packetery\Checkout\Model\Weight\Unit;

class WeightUnitTest extends \Packetery\Checkout\Test\BaseTest
{
    /**
     * @dataProvider fromRawProvider
     */
    public function testFromRaw(?string $input, ?Unit $expected): void
    {
        $this->assertSame($expected, Unit::fromRaw($input));
    }

    public static function fromRawProvider(): array
    {
        return [
            'null input' => [null, null],
            'empty string' => ['', null],
            'whitespace only' => ['   ', null],
            'kg lowercase' => ['kg', Unit::KG],
            'kg uppercase' => ['KG', Unit::KG],
            'kg with spaces' => ['  kg  ', Unit::KG],
            'kgs lowercase' => ['kgs', Unit::KG],
            'kgs uppercase' => ['KGS', Unit::KG],
            'kgs with spaces' => ['  kgs  ', Unit::KG],
            'g lowercase' => ['g', Unit::GRAM],
            'G uppercase' => ['G', Unit::GRAM],
            'g with spaces' => ['  g  ', Unit::GRAM],
            'gms' => ['gms', Unit::GRAM],
            'GMS uppercase' => ['GMS', Unit::GRAM],
            'GMS with spaces' => ['  gms  ', Unit::GRAM],
            'lb lowercase' => ['lb', Unit::LB],
            'LB uppercase' => ['LB', Unit::LB],
            'lb with spaces' => [' lb ', Unit::LB],
            'LBS lowercase' => ['lbs', Unit::LB],
            'LBS uppercase' => ['LBS', Unit::LB],
            'lbs with spaces' => [' lbs ', Unit::LB],
            'unknown unit' => ['oz', null],
            'random string' => ['foobar', null],
        ];
    }

    public function testGetMultiplierKg(): void
    {
        $this->assertSame(1.000, Unit::KG->getMultiplier());
    }

    public function testGetMultiplierGram(): void
    {
        $this->assertSame(0.001, Unit::GRAM->getMultiplier());
    }

    public function testGetMultiplierLb(): void
    {
        $this->assertSame(0.45359237, Unit::LB->getMultiplier());
    }

    public function testCaseValues(): void
    {
        $this->assertSame('kg', Unit::KG->value);
        $this->assertSame('g', Unit::GRAM->value);
        $this->assertSame('lb', Unit::LB->value);
    }
}
