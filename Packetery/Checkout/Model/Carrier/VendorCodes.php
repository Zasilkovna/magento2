<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier;

use Magento\Framework\Phrase;

class VendorCodes
{
    public const CZZPOINT = 'czzpoint';
    public const CZALZABOX = 'czalzabox';
    public const CZZBOX = 'czzbox';
    public const SKZPOINT = 'skzpoint';
    public const SKZBOX = 'skzbox';
    public const HUZPOINT = 'huzpoint';
    public const HUZBOX = 'huzbox';
    public const ROZPOINT = 'rozpoint';
    public const ROZBOX = 'rozbox';

    /**
     * @param string $code
     * @return \Magento\Framework\Phrase
     */
    public static function getLabel(string $code): Phrase {
        $mapping = [
            self::ROZBOX => __('Z-BOX'),
            self::HUZBOX => __('Z-BOX'),
            self::CZZBOX => __('Z-BOX'),
            self::SKZBOX => __('Z-BOX'),
            self::CZALZABOX => __('AlzaBox'),
            self::CZZPOINT => __('Internal pickup points'),
            self::HUZPOINT => __('Internal pickup points'),
            self::ROZPOINT => __('Internal pickup points'),
            self::SKZPOINT => __('Internal pickup points'),
        ];

        return $mapping[$code];
    }

    /**
     * @param string[] $codes
     */
    public static function encode(array $codes): string {
        return json_encode(array_values($codes), JSON_THROW_ON_ERROR);
    }

    /**
     * @param string $encodedCodes
     * @return string[]
     */
    public static function decode(string $encodedCodes): array {
        return json_decode($encodedCodes, true, 512, JSON_THROW_ON_ERROR);
    }
}
