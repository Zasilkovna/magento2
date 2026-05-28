<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\Order\Listing\Column;

class ConsignPassword extends \Magento\Ui\Component\Listing\Columns\Column
{
    /** @var \Packetery\Checkout\Model\Carrier\Facade */
    private $carrierFacade;

    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        \Packetery\Checkout\Model\Carrier\Facade $carrierFacade,
        array $components = [],
        array $data = []
    ) {
        $this->carrierFacade = $carrierFacade;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        $name = $this->getData('name');

        foreach ($dataSource['data']['items'] as &$item) {
            $storeId = isset($item['store_id']) ? (int) $item['store_id'] : 0;
            $config = $this->carrierFacade->getPacketeryCarrierConfig($storeId);
            if ($config === null || !$config->isShowConsignPassword()) {
                $item[$name] = '';
            }
        }

        return $dataSource;
    }
}
