<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Label;

final class LabelFormats
{
    public const DEFAULT_FORMAT = 'A6 on A4';

    /** @var array<string, int> */
    private const MAX_OFFSET_BY_FORMAT = [
        'A6 on A4' => 3,
        'A6 on A6' => 0,
        'A7 on A7' => 0,
        'A7 on A4' => 7,
        '105x35mm on A4' => 15,
        'A8 on A8' => 0,
    ];

    /** @var string[] */
    private const CARRIER_ALLOWED_FORMATS = [
        'A6 on A4',
        'A6 on A6',
    ];

    public static function getMaxOffset(string $format): int
    {
        if (!isset(self::MAX_OFFSET_BY_FORMAT[$format])) {
            return 0;
        }

        return self::MAX_OFFSET_BY_FORMAT[$format];
    }

    /**
     * @return string[]
     */
    public static function getAllFormatKeys(): array
    {
        return array_keys(self::MAX_OFFSET_BY_FORMAT);
    }

    /**
     * @return string[]
     */
    public static function getCarrierFormatKeys(): array
    {
        return self::CARRIER_ALLOWED_FORMATS;
    }

    public static function isKnownFormat(string $format): bool
    {
        return isset(self::MAX_OFFSET_BY_FORMAT[$format]);
    }

    public static function isCarrierFormatAllowed(string $format): bool
    {
        return in_array($format, self::CARRIER_ALLOWED_FORMATS, true);
    }

    public static function normalizePacketaFormat(string $value): string
    {
        if ($value !== '' && self::isKnownFormat($value)) {
            return $value;
        }

        return self::DEFAULT_FORMAT;
    }

    public static function normalizeCarrierFormat(string $value): string
    {
        if ($value !== '' && self::isCarrierFormatAllowed($value)) {
            return $value;
        }

        return self::DEFAULT_FORMAT;
    }

    /**
     * @return array<string, array{
     *     name: \Magento\Framework\Phrase,
     *     directLabels: bool,
     *     maxOffset: int
     * }>
     */
    public static function getLabelFormatDefinitions(): array
    {
        return [
            'A6 on A4' => [
                'name' => __('1/4 A4, print on A4, 4pcs/page'),
                'directLabels' => true,
                'maxOffset' => self::MAX_OFFSET_BY_FORMAT['A6 on A4'],
            ],
            'A6 on A6' => [
                'name' => __('1/4 A4, direct print, 1pc/page'),
                'directLabels' => true,
                'maxOffset' => self::MAX_OFFSET_BY_FORMAT['A6 on A6'],
            ],
            'A7 on A7' => [
                'name' => __('1/8 A4, direct print, 1pc/page'),
                'directLabels' => false,
                'maxOffset' => self::MAX_OFFSET_BY_FORMAT['A7 on A7'],
            ],
            'A7 on A4' => [
                'name' => __('1/8 A4, print on A4, 8pcs/page'),
                'directLabels' => false,
                'maxOffset' => self::MAX_OFFSET_BY_FORMAT['A7 on A4'],
            ],
            '105x35mm on A4' => [
                'name' => __('105x35mm, print on A4, 16 pcs/page'),
                'directLabels' => false,
                'maxOffset' => self::MAX_OFFSET_BY_FORMAT['105x35mm on A4'],
            ],
            'A8 on A8' => [
                'name' => __('1/16 A4, direct print, 1pc/page'),
                'directLabels' => false,
                'maxOffset' => self::MAX_OFFSET_BY_FORMAT['A8 on A8'],
            ],
        ];
    }
}
