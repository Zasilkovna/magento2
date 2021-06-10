<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier\Imp\PacketeryPacketaDynamic;

/**
 * Optional sub-carrier of Magento fixed carrier
 */
class DynamicCarrier extends \Packetery\Checkout\Model\Carrier\AbstractDynamicCarrier
{
    /** @var \Packetery\Checkout\Model\Carrier */
    private $model;

    /**
     * DynamicCarrier constructor.
     *
     * @param \Packetery\Checkout\Model\Carrier $model
     */
    public function __construct(\Packetery\Checkout\Model\Carrier $model) {
        $this->model = $model;
    }

    public function getCarrierId(): int {
        return $this->model->getCarrierId();
    }

    public function getCountryId(): string {
        return $this->model->getCountryId();
    }

    public function getDeleted(): bool {
        return $this->model->getDeleted();
    }

    public function getFinalCarrierName(): string {
        return $this->model->getFinalCarrierName();
    }

    public function getMethods(): array {
        return [$this->model->getMethod()];
    }
}
