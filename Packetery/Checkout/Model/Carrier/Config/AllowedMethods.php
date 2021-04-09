<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier\Config;

class AllowedMethods
{
    public const PICKUP_POINT_DELIVERY = 'pickupPointDelivery';
    public const ADDRESS_DELIVERY = 'addressDelivery';

    /** @var string[] */
    private $allowedMethods;

    /**
     * AllowedMethods constructor.
     *
     * @param string[] $allowedMethods
     */
    public function __construct(array $allowedMethods)
    {
        $this->allowedMethods = $allowedMethods;
    }

    /**
     * @return bool
     */
    public function hasPickupPointAllowed(): bool
    {
        return $this->hasAllowed(self::PICKUP_POINT_DELIVERY);
    }

    /**
     * @return bool
     */
    public function hasAddressDeliveryAllowed(): bool
    {
        return $this->hasAllowed(self::ADDRESS_DELIVERY);
    }

    public function hasAllowed(string $method)
    {
        return empty($this->allowedMethods) || in_array($method, $this->allowedMethods);
    }
}
