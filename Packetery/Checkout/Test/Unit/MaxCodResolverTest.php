<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit;

use Packetery\Checkout\Model\Carrier;
use Packetery\Checkout\Model\MaxCodResolver;
use Packetery\Checkout\Model\Pricingrule;
use Packetery\Checkout\Model\ResourceModel\Carrier\Collection;
use Packetery\Checkout\Model\ResourceModel\Carrier\CollectionFactory;
use PHPUnit\Framework\TestCase;

class MaxCodResolverTest extends TestCase
{
    /**
     * @return array<string, array{?float, ?int, bool|null, ?float}>
     */
    public static function resolveDataProvider(): array
    {
        return [
            'max_cod null' => [null, 123, null, null],
            'carrier_id null returns max_cod' => [100.0, null, null, 100.0],
            'carrier not in collection returns max_cod' => [50.0, 999, null, 50.0],
            'carrier disallows cod returns null' => [200.0, 1, true, null],
            'carrier allows cod returns max_cod' => [150.0, 1, false, 150.0],
        ];
    }

    /**
     * @dataProvider resolveDataProvider
     */
    public function testResolve(?float $maxCod, ?int $carrierId, ?bool $disallowsCod, ?float $expected): void
    {
        $pricingRule = $this->createPricingRuleMock($maxCod, $carrierId);
        $carrier = $disallowsCod !== null ? $this->createCarrierMock($disallowsCod) : null;
        $collection = $this->createCollectionMock($carrierId, $carrier);
        $collectionFactory = $this->createCollectionFactoryMock($collection);

        $resolver = new MaxCodResolver($collectionFactory);
        $this->assertSame($expected, $resolver->resolve($pricingRule));
    }

    private function createPricingRuleMock(?float $maxCod, ?int $carrierId): Pricingrule
    {
        $pricingRule = $this->createMock(Pricingrule::class);
        $pricingRule->method('getMaxCOD')->willReturn($maxCod);
        $pricingRule->method('getCarrierId')->willReturn($carrierId);
        return $pricingRule;
    }

    private function createCarrierMock(bool $disallowsCod): Carrier
    {
        $carrier = $this->createMock(Carrier::class);
        $carrier->method('disallowsCod')->willReturn($disallowsCod);
        return $carrier;
    }

    /**
     * @param Carrier|null $carrier
     */
    private function createCollectionMock(?int $carrierId, ?Carrier $carrier): Collection
    {
        $collection = $this->createMock(Collection::class);
        $collection->method('getItemByColumnValue')
            ->with('carrier_id', $carrierId)
            ->willReturn($carrier);
        return $collection;
    }

    private function createCollectionFactoryMock(Collection $collection): CollectionFactory
    {
        $factory = $this->createMock(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);
        return $factory;
    }
}
