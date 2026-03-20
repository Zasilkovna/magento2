<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\Box\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Packetery\Checkout\Model\Box;

class Actions extends Column
{
    public const URL_PATH_EDIT = 'packetery/box/detail';
    public const URL_PATH_DELETE = 'packetery/box/delete';

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item[Box::ID])) {
                    $item[$this->getData('name')]['edit'] = [
                        'href' => $this->urlBuilder->getUrl(self::URL_PATH_EDIT, [Box::ID => $item[Box::ID]]),
                        'label' => __('Edit'),
                    ];

                    $item[$this->getData('name')]['delete'] = [
                        'href' => $this->urlBuilder->getUrl(self::URL_PATH_DELETE, [Box::ID => $item[Box::ID]]),
                        'label' => __('Delete'),
                        'confirm' => [
                            'title' => __('Delete'),
                            'message' => __("Are you sure you want to delete item '%1'?", $item['name'])
                        ],
                    ];
                }
            }
        }

        return $dataSource;
    }
}
