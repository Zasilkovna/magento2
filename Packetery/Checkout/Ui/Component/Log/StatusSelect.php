<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\Log;

use Magento\Framework\Phrase;

class StatusSelect implements \Magento\Framework\Data\OptionSourceInterface
{
    public const STATUS_SUCCESS = 'success';
    public const STATUS_ERROR   = 'error';

    /**
     * @return mixed[]
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::STATUS_SUCCESS, 'label' => __('Success')],
            ['value' => self::STATUS_ERROR, 'label' => __('Error')],
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
