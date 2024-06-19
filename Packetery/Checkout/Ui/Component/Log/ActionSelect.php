<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\Log;

use Magento\Framework\Phrase;

class ActionSelect implements \Magento\Framework\Data\OptionSourceInterface
{
    public const ACTION_PACKET_SENDING = 'packet-sending';
    public const ACTION_LABEL_PRINT = 'label-print';
    public const ACTION_CARRIER_LABEL_PRINT = 'carrier-label-print';
    public const ACTION_SENDER_VALIDATION = 'sender-validation';
    public const ACTION_PACKET_CANCEL = 'packet-cancel';

    public function toOptionArray(): array
    {
        return [
            ['value' => self::ACTION_PACKET_SENDING, 'label' => __('Packet sending')],
            ['value' => self::ACTION_LABEL_PRINT, 'label' => __('Label print')],
            ['value' => self::ACTION_CARRIER_LABEL_PRINT, 'label' => __('Carrier label print')],
            ['value' => self::ACTION_PACKET_CANCEL, 'label' => __('Packet cancel')],
            ['value' => self::ACTION_SENDER_VALIDATION, 'label' => __('Sender validation')],
        ];
    }

    /**
     * @return array<string, Phrase>
     */
    public function toLabelArray(): array
    {
        $labels = [];

        foreach ($this->toOptionArray() as $option) {
            $labels[$option['value']] = $option['label'];
        }

        return $labels;
    }
}
