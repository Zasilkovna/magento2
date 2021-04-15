<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\Pricingrule\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;
use Packetery\Checkout\Model\Carrier\Config\AllowedMethods;

class Method extends Column
{
    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {

                $phrase = null;
                switch ($item["method"]) {
                    case AllowedMethods::PICKUP_POINT_DELIVERY:
                        $phrase = __('Pickup Point Delivery Method');
                        break;
                    case AllowedMethods::ADDRESS_DELIVERY:
                        $phrase = __('Address Delivery Method');
                        break;
                }

                $item[$this->getData('name')] = ($phrase !== null ? $phrase : $item["method"]);
            }
        }

        return $dataSource;
    }
}
