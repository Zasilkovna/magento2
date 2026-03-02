<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model;

use Packetery\Checkout\Model\Carrier\Methods;

/**
 * Address validation via HD widget is only supported for CZ and SK HD (address delivery) carriers.
 */
class AddressValidationResolver
{
    /** @var string[] Countries where address validation can be enabled */
    public const ADDRESS_VALIDATION_COUNTRIES = ['CZ', 'SK'];

    /**
     * Whether address validation can be shown/used for this method and country.
     */
    public static function isEligibleForAddressValidation(string $method, string $countryId): bool
    {
        return Methods::isAnyAddressDelivery($method) && in_array($countryId, self::ADDRESS_VALIDATION_COUNTRIES, true);
    }

    /**
     * Effective address validation from pricing rule: NONE when not HD, or country not in allowed list.
     *
     * @param Pricingrule|null $pricingRule
     * @return string
     */
    public function resolve(?Pricingrule $pricingRule): string
    {
        if ($pricingRule === null) {
            return AddressValidationSelect::NONE;
        }

        $method = $pricingRule->getMethod();
        $countryId = $pricingRule->getCountryId();
        if (
            $method === null ||
            $countryId === null ||
            !self::isEligibleForAddressValidation($method, $countryId)
        ) {
            return AddressValidationSelect::NONE;
        }

        return $pricingRule->getAddressValidation();
    }
}
