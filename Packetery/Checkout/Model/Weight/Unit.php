<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Weight;

enum Unit: string
{
    case KG = 'kg';
    case GRAM = 'g';
    case LB = 'lb';

    private const MULTIPLIER_KG = 1.0;
    private const MULTIPLIER_GRAM = 0.001;
    private const MULTIPLIER_LB = 0.45359237;

    public static function fromRaw(?string $rawUnit): ?self
    {
        if (!$rawUnit) {
            return null;
        }

        $normalized = strtolower(trim($rawUnit));
        return match (true) {
            $normalized === 'kgs',
                $normalized === 'kg' => self::KG,
            $normalized === 'gms',
                $normalized === 'g' => self::GRAM,
            $normalized === 'lbs',
                $normalized === 'lb' => self::LB,
            default => null,
        };
    }

    public function getMultiplier(): float
    {
        return match ($this) {
            self::KG => self::MULTIPLIER_KG,
            self::GRAM => self::MULTIPLIER_GRAM,
            self::LB => self::MULTIPLIER_LB,
        };
    }
}
