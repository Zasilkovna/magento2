<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit;

use Packetery\Checkout\Model\AddressValidationResolver;
use Packetery\Checkout\Model\AddressValidationSelect;
use Packetery\Checkout\Model\Carrier\Methods;
use Packetery\Checkout\Model\Pricingrule;
use PHPUnit\Framework\TestCase;

class AddressValidationResolverTest extends TestCase
{
    /**
     * @return array<string, array{string, string, bool}>
     */
    public static function isEligibleForAddressValidationDataProvider(): array
    {
        return [
            'address delivery CZ' => [Methods::LEGACY_BEST_DELIVERY_SOLUTION, 'CZ', true],
            'address delivery SK' => [Methods::LEGACY_BEST_DELIVERY_SOLUTION, 'SK', true],
            'direct address delivery CZ' => [Methods::DIRECT_ADDRESS_DELIVERY, 'CZ', true],
            'direct address delivery SK' => [Methods::DIRECT_ADDRESS_DELIVERY, 'SK', true],
            'address delivery DE' => [Methods::LEGACY_BEST_DELIVERY_SOLUTION, 'DE', false],
            'address delivery empty country' => [Methods::LEGACY_BEST_DELIVERY_SOLUTION, '', false],
            'pickup point CZ' => [Methods::PICKUP_POINT_DELIVERY, 'CZ', false],
            'legacy pickup point SK' => [Methods::LEGACY_PICKUP_POINT_DELIVERY, 'SK', false],
            'unknown method CZ' => ['unknownMethod', 'CZ', false],
        ];
    }

    /**
     * @dataProvider isEligibleForAddressValidationDataProvider
     */
    public function testIsEligibleForAddressValidation(string $method, string $countryId, bool $expected): void
    {
        $this->assertSame($expected, AddressValidationResolver::isEligibleForAddressValidation($method, $countryId));
    }

    public function testResolveReturnsNoneWhenPricingRuleIsNull(): void
    {
        $resolver = new AddressValidationResolver();
        $this->assertSame(AddressValidationSelect::NONE, $resolver->resolve(null));
    }

    public function testResolveReturnsNoneWhenMethodIsNull(): void
    {
        $pricingRule = $this->createPricingRuleMock(null, 'CZ', AddressValidationSelect::OPTIONAL);
        $resolver = new AddressValidationResolver();
        $this->assertSame(AddressValidationSelect::NONE, $resolver->resolve($pricingRule));
    }

    public function testResolveReturnsNoneWhenCountryIdIsNull(): void
    {
        $pricingRule = $this->createPricingRuleMock(Methods::DIRECT_ADDRESS_DELIVERY, null, AddressValidationSelect::OPTIONAL);
        $resolver = new AddressValidationResolver();
        $this->assertSame(AddressValidationSelect::NONE, $resolver->resolve($pricingRule));
    }

    public function testResolveReturnsNoneWhenNotEligiblePickupPoint(): void
    {
        $pricingRule = $this->createPricingRuleMock(Methods::PICKUP_POINT_DELIVERY, 'CZ', AddressValidationSelect::OPTIONAL);
        $resolver = new AddressValidationResolver();
        $this->assertSame(AddressValidationSelect::NONE, $resolver->resolve($pricingRule));
    }

    public function testResolveReturnsNoneWhenNotEligibleCountry(): void
    {
        $pricingRule = $this->createPricingRuleMock(Methods::DIRECT_ADDRESS_DELIVERY, 'DE', AddressValidationSelect::OPTIONAL);
        $resolver = new AddressValidationResolver();
        $this->assertSame(AddressValidationSelect::NONE, $resolver->resolve($pricingRule));
    }

    public function testResolveReturnsPricingRuleAddressValidationWhenEligible(): void
    {
        $pricingRule = $this->createPricingRuleMock(Methods::DIRECT_ADDRESS_DELIVERY, 'CZ', AddressValidationSelect::REQUIRED);
        $resolver = new AddressValidationResolver();
        $this->assertSame(AddressValidationSelect::REQUIRED, $resolver->resolve($pricingRule));
    }

    public function testResolveReturnsOptionalWhenEligibleAndRuleOptional(): void
    {
        $pricingRule = $this->createPricingRuleMock(Methods::LEGACY_BEST_DELIVERY_SOLUTION, 'SK', AddressValidationSelect::OPTIONAL);
        $resolver = new AddressValidationResolver();
        $this->assertSame(AddressValidationSelect::OPTIONAL, $resolver->resolve($pricingRule));
    }

    /**
     * @param string|null $method
     * @param string|null $countryId
     */
    private function createPricingRuleMock(?string $method, ?string $countryId, string $addressValidation): Pricingrule
    {
        $pricingRule = $this->createMock(Pricingrule::class);
        $pricingRule->method('getMethod')->willReturn($method);
        $pricingRule->method('getCountryId')->willReturn($countryId);
        $pricingRule->method('getAddressValidation')->willReturn($addressValidation);
        return $pricingRule;
    }
}
