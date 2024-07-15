<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\Log\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Packetery\Checkout\Ui\Component\Log\RequestSelect;

class RequestName extends Column
{
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        protected RequestSelect $requestSelect,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            $labels = $this->requestSelect->toLabelArray();
            foreach ($dataSource['data']['items'] as &$item) {
                $item[$this->getData('name')] = $labels[$item['action']] ?? $item['action'];
            }
        }

        return $dataSource;
    }

    protected function applySorting(): void
    {
        // no DB select sorting
    }
}
