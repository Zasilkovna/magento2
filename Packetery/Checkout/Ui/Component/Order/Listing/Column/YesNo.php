<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\Order\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

class YesNo extends Column
{
    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $value = $item[$this->getData('name')];
                $item[$this->getData('name')] = ($value ? __('Yes') : __('No'));
            }
        }

        return $dataSource;
    }

    protected function applySorting() {
        // no DB select sorting
    }
}
