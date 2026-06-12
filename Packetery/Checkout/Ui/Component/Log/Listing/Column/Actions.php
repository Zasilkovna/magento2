<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\Log\Listing\Column;

class Actions extends \Magento\Ui\Component\Listing\Columns\Column
{
    public const URL_PATH_DETAIL = 'packetery/log/detail';

    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        private readonly \Magento\Framework\UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        $fieldName = $this->getData('name');
        foreach ($dataSource['data']['items'] as &$item) {
            if (
                !isset($item[\Packetery\Checkout\Model\Log::ID])
                || ($item[\Packetery\Checkout\Model\Log::STATUS] ?? '') !== \Packetery\Checkout\Model\Log::STATUS_ERROR
            ) {
                continue;
            }

            $item[$fieldName]['detail'] = [
                'href' => $this->urlBuilder->getUrl(
                    self::URL_PATH_DETAIL,
                    [\Packetery\Checkout\Model\Log::ID => $item[\Packetery\Checkout\Model\Log::ID], 'modal' => 1]
                ),
                'label' => __('Detail'),
            ];
        }

        return $dataSource;
    }
}
