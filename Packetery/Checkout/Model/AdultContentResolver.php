<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model;

use Packetery\Checkout\Model\Carrier\Imp\Packetery\Brain;
use Packetery\Checkout\Model\Carrier\Methods;

/**
 * Adult content verification is only supported on Packeta's own pickup-point / Z-BOX network in base countries
 */
class AdultContentResolver
{
    public static function isEligibleForAdultContent(string $method, string $countryId, bool $isCarrier): bool
    {
        return Methods::isPickupPointDelivery($method)
            && $isCarrier === false
            && in_array($countryId, Brain::BASE_COUNTRIES, true);
    }
}
