<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\Order;

use Magento\Framework\Data\OptionSourceInterface;
use Packetery\Checkout\Model\Box;
use Packetery\Checkout\Model\Dimensions\Converter;
use Packetery\Checkout\Model\ResourceModel\Box\CollectionFactory;

class BoxSelect implements OptionSourceInterface
{
    public function __construct(
        private readonly CollectionFactory $collectionFactory,
        private readonly Converter $dimensionsConverter
    ) {
    }

    /**
     * @return array<int, array{label: string, value: int, disabled: bool}>
     */
    public function toOptionArray(): array
    {
        $options = [];
        foreach ($this->collectionFactory->create() as $box) {
            $options[] = [
                'label' => $this->formatLabel($box),
                'value' => (int) $box->getId(),
                'disabled' => (bool) $box->getDeleted(),
            ];
        }

        return $options;
    }

    /**
     * Bare name when any dimension is missing (only reachable via direct DB edit),
     * so label()'s non-null floats can't throw
     */
    private function formatLabel(Box $box): string
    {
        $name = (string) $box->getName();
        $depth = $box->getDepth();
        $width = $box->getWidth();
        $height = $box->getHeight();
        if ($depth === null || $width === null || $height === null) {
            return $name;
        }

        return $this->dimensionsConverter->label($name, $depth, $width, $height);
    }
}
