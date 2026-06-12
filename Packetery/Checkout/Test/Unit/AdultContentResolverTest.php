<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit;

use Packetery\Checkout\Model\AdultContentResolver;
use Packetery\Checkout\Model\Carrier\Methods;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AdultContentResolverTest extends TestCase
{
    /**
     * @return array<string, array{string, string, bool, bool}>
     */
    public static function isEligibleForAdultContentDataProvider(): array
    {
        return [
            'own pickup point CZ' => [Methods::PICKUP_POINT_DELIVERY, 'CZ', false, true],
            'own pickup point SK' => [Methods::PICKUP_POINT_DELIVERY, 'SK', false, true],
            'own pickup point HU' => [Methods::PICKUP_POINT_DELIVERY, 'HU', false, true],
            'own pickup point RO' => [Methods::PICKUP_POINT_DELIVERY, 'RO', false, true],
            'legacy own pickup point CZ' => [Methods::LEGACY_PICKUP_POINT_DELIVERY, 'CZ', false, true],
            'carrier pickup point CZ' => [Methods::PICKUP_POINT_DELIVERY, 'CZ', true, false],
            'carrier pickup point SK' => [Methods::PICKUP_POINT_DELIVERY, 'SK', true, false],
            'pickup point PL' => [Methods::PICKUP_POINT_DELIVERY, 'PL', false, false],
            'pickup point DE' => [Methods::PICKUP_POINT_DELIVERY, 'DE', false, false],
            'pickup point empty country' => [Methods::PICKUP_POINT_DELIVERY, '', false, false],
            'address delivery CZ' => [Methods::DIRECT_ADDRESS_DELIVERY, 'CZ', false, false],
            'legacy address delivery CZ' => [Methods::LEGACY_BEST_DELIVERY_SOLUTION, 'CZ', false, false],
            'unknown method CZ' => ['unknownMethod', 'CZ', false, false],
        ];
    }

    #[DataProvider('isEligibleForAdultContentDataProvider')]
    public function testIsEligibleForAdultContent(string $method, string $countryId, bool $isCarrier, bool $expected): void
    {
        $this->assertSame($expected, AdultContentResolver::isEligibleForAdultContent($method, $countryId, $isCarrier));
    }
}
