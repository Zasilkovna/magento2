<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier\Imp\Packetery;

use Packetery\Checkout\Model\Carrier\Methods;

class VendorCarrier extends \Packetery\Checkout\Model\Carrier\AbstractDynamicCarrier
{

    /**
     * @var int
     */
    private $carrierId;

    /**
     * @var string
     */
    private $vendorCode;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $countryId;

    public function __construct(int $carrierId, string $vendorCode, string $title, string $countryId) {
        $this->carrierId = $carrierId;
        $this->vendorCode = $vendorCode;
        $this->title = $title;
        $this->countryId = $countryId;
    }

    public function getCarrierId(): int {
        return $this->carrierId;
    }

    public function getVendorCode(): string {
        return $this->vendorCode;
    }

    public function getName(): string {
        return sprintf('%s %s', $this->countryId, $this->title);
    }

    public function getCountryId(): string {
        return $this->countryId;
    }

    public function getMaxWeight(): ?float {
        return null;
    }

    public function getDeleted(): bool {
        return false;
    }

    public function getFinalCarrierName(): string {
        return $this->title;
    }

    /**
     * @return string[]
     */
    public function getMethods(): array {
        return [Methods::PICKUP_POINT_DELIVERY];
    }
}
