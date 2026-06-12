<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Dimensions;

/**
 * Converter for box dimensions: Packet details have it in cm, but Packeta API accepts mm
 */
class Converter
{
    /** Converts a value between units (e.g. cm to mm); the caller rounds to whole mm for the API */
    public function convert(float $value, Unit $from, Unit $to): float
    {
        return $value * $from->getMultiplier() / $to->getMultiplier();
    }

    /**
     * Formats a single cm dimension for display: one decimal, trailing zeros dropped ("30.0" to "30", "15.5" kept)
     */
    public function formatCm(float $cm): string
    {
        $oneDecimal = number_format($cm, 1, '.', '');
        $withoutTrailingZeros = rtrim($oneDecimal, '0');

        return rtrim($withoutTrailingZeros, '.');
    }

    /** Composes a box label like "M (30 × 20 × 10 cm)" */
    public function label(
        string $name,
        float $depth,
        float $width,
        float $height
    ): string {
        return sprintf(
            '%s (%s × %s × %s cm)',
            $name,
            $this->formatCm($depth),
            $this->formatCm($width),
            $this->formatCm($height)
        );
    }
}
