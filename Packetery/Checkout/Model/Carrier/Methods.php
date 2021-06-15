<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier;

/**
 * Represents all possible carrier methods
 */
class Methods
{
    public const PICKUP_POINT_DELIVERY = 'pickupPointDelivery';
    public const ADDRESS_DELIVERY = 'addressDelivery';

    /**
     * @return string[]
     */
    public static function getAll(): array {
        return [
            self::PICKUP_POINT_DELIVERY,
            self::ADDRESS_DELIVERY
        ];
    }
}
