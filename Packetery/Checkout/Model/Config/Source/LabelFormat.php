<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class LabelFormat implements OptionSourceInterface
{
    /** @var string[] */
    protected array $keys;

    public function __construct()
    {
        $this->keys = \Packetery\Checkout\Model\Label\LabelFormats::getAllFormatKeys();
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        $definitions = \Packetery\Checkout\Model\Label\LabelFormats::getLabelFormatDefinitions();

        $options = [];
        foreach ($this->keys as $key) {
            $options[] = [
                'value' => $key,
                'label' => $definitions[$key]['name'] ?? __($key),
            ];
        }

        return $options;
    }
}
