<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier;

abstract class AbstractDynamicCarrier
{
    abstract public function getCarrierId(): int;

    abstract public function getCountryId(): string;

    abstract public function getDeleted(): bool;

    abstract public function getFinalCarrierName(): string;

    abstract public function getMethods(): array;
}
