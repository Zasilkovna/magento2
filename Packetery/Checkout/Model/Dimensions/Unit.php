<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Dimensions;

enum Unit: string
{
    case MM = 'mm';
    case CM = 'cm';

    private const MULTIPLIER_MM = 1.0;
    private const MULTIPLIER_CM = 10.0;

    public function getMultiplier(): float
    {
        return match ($this) {
            self::MM => self::MULTIPLIER_MM,
            self::CM => self::MULTIPLIER_CM,
        };
    }
}
