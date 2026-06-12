<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Log;

class StatusOptions implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        $options = [];
        foreach (
            [
                \Packetery\Checkout\Model\Log::STATUS_SUCCESS,
                \Packetery\Checkout\Model\Log::STATUS_ERROR,
            ] as $status
        ) {
            $options[] = [
                'value' => $status,
                'label' => $this->getLabel($status),
            ];
        }

        return $options;
    }

    public function getLabel(string $status): \Magento\Framework\Phrase
    {
        return match ($status) {
            \Packetery\Checkout\Model\Log::STATUS_SUCCESS => __('Success'),
            \Packetery\Checkout\Model\Log::STATUS_ERROR => __('Error'),
            default => __($status),
        };
    }
}
