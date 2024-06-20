<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Packeta;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

class DataProvider extends AbstractDataProvider
{
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $orderCollectionFactory,
        private readonly \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $orderFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $orderCollectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        $result = [];
        foreach ($this->collection->getItems() as $item) {
            $orderNumber = $item->getDataByKey('increment_id');
            $order = $this->orderFactory->create()->getItemByColumnValue('order_number', $orderNumber)->getData();

            $result[$item->getId()]['general'] = [
                'magento_order_id' => $item->getDataByKey('entity_id'),
                'order_id'         => $order['id'],
                'order_value'      => $order['value'],
                'cod_value'        => $order['cod'] ?? 0,
                'weight'           => $order['weight'] ?? 0,
                'length'           => $order['depth'] ?? 0,
                'height'           => $order['height'] ?? 0,
                'width'            => $order['width'] ?? 0,
                'adult_content'    => $order['adult_content'] ?? false,
                'planned_dispatch' => $order['delayed_delivery'] ?? null,
            ];
        }

        return $result;
    }
}
