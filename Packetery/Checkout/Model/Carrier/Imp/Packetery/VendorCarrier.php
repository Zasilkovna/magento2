<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier\Imp\Packetery;

use Packetery\Checkout\Model\Carrier\Methods;

class VendorCarrier extends \Packetery\Checkout\Model\Carrier\AbstractDynamicCarrier
{
    /**
     * @var int
     */
    private $dynamicCarrierId;

    /**
     * @var string
     */
    private $group;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $countryId;

    public function __construct(int $dynamicCarrierId, string $group, string $title, string $countryId)
    {
        $this->dynamicCarrierId = $dynamicCarrierId;
        $this->group = $group;
        $this->title = $title;
        $this->countryId = $countryId;
    }

    public function getDynamicCarrierId(): int
    {
        return $this->dynamicCarrierId;
    }

    public function getGroup(): string
    {
        return $this->group;
    }

    public function getName(): string
    {
        return sprintf('%s %s', $this->countryId, $this->title);
    }

    public function getCountryId(): string
    {
        return $this->countryId;
    }

    public function getMaxWeight(): ?float
    {
        return null;
    }

    public function getDeleted(): bool
    {
        return false;
    }
    public function getFinalCarrierName(): string
    {
        return $this->title;
    }

    /**
     * @return string[]
     */
    public function getMethods(): array
    {
        return [Methods::PICKUP_POINT_DELIVERY];
    }
}
