<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\Log\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Packetery\Checkout\Ui\Component\Log\ActionSelect;

class Action extends Column
{
    /** @var \Packetery\Checkout\Ui\Component\Log\ActionSelect */
    protected $actionSelect;

    /**
     * @param mixed[] $components
     * @param mixed[] $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        ActionSelect $actionSelect,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->actionSelect = $actionSelect;
    }

    /**
     * @param mixed[] $dataSource
     * @return mixed[]
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            $labels = $this->actionSelect->toLabelArray();
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
