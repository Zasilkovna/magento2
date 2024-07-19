<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\Log\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Actions extends Column
{
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $name = $this->getData('name');
                $item[$name]['edit'] = [
                    'callback' => [
                        [
                            'provider' => 'packetery_log_listing.test_modal'
                                . '.log_detail.info',
                            'target' => 'destroyInserted',
                        ],
                        [
                            'provider' => 'packetery_checkout_log_listing.test_modal',
                            'target' => 'openModal',
                        ],
                        [
                            'provider' => 'packetery_log_listing.test_modal'
                                . '.log_detail.info',
                            'target' => 'render',
                            'params' => [
                                'entity_id' => $item['id'],
                            ],
                        ]
                    ],
                    'href' => '#',
                    'label' => __('View'),
                    'hidden' => false,
                ];
            }
        }

        return $dataSource;
    }
}
