<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\Log\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

class Actions extends Column
{
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $name = $this->getData('name');
                $item[$name]['view'] = [
                    'href' => '#',
                    'label' => __('View'),
                    'data_attribute' => [
                        'mage-init' => [
                            'Magento_Ui/js/form/button-adapter' => [
                                'actions' => [
                                    [
                                        'targetName' => 'packetery_.log_modal',
                                        'actionName' => 'toggleModal'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'on_click' => '',
                    'sort_order' => 10
                ];
            }
        }
        return $dataSource;
    }

}
