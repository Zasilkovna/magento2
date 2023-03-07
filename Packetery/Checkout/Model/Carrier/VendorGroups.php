<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier;

use Magento\Framework\Phrase;

class VendorGroups
{
    public const ZPOINT = 'zpoint';
    public const ALZABOX = 'alzabox';
    public const ZBOX = 'zbox';

    /**
     * @param string $group
     * @return \Magento\Framework\Phrase
     */
    public static function getLabel(string $group): Phrase {
        $mapping = [
            self::ZPOINT => __('Internal pickup points'),
            self::ALZABOX => __('AlzaBox'),
            self::ZBOX => __('Z-BOX'),
        ];

        return $mapping[$group];
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
