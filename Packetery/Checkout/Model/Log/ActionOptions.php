<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Log;

class ActionOptions implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        $options = [];
        foreach (
            [
                \Packetery\Checkout\Model\Log::ACTION_SUBMIT,
                \Packetery\Checkout\Model\Log::ACTION_CANCEL,
                \Packetery\Checkout\Model\Log::ACTION_PRINT_LABEL,
                \Packetery\Checkout\Model\Log::ACTION_PRINT_LIST,
            ] as $action
        ) {
            $options[] = [
                'value' => $action,
                'label' => $this->getLabel($action),
            ];
        }

        return $options;
    }

    public function getLabel(string $action): \Magento\Framework\Phrase
    {
        return match ($action) {
            \Packetery\Checkout\Model\Log::ACTION_SUBMIT => __('Submit packet'),
            \Packetery\Checkout\Model\Log::ACTION_CANCEL => __('Cancel packet'),
            \Packetery\Checkout\Model\Log::ACTION_PRINT_LABEL => __('Print label'),
            \Packetery\Checkout\Model\Log::ACTION_PRINT_LIST => __('Print packet list'),
            default => __($action),
        };
    }
}
