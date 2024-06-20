<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Packetdraft;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Packetery\Checkout\Model\Order;

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
            /** @var Order $order */
            $order = $this->orderFactory->create()->getItemByColumnValue('order_number', $orderNumber);

            $result[$item->getId()]['general'] = [
                'magento_order_id' => $item->getDataByKey('entity_id'),
                'order_id'         => $order->getId(),
                'order_value'      => $order->getValue(),
                'cod_value'        => $order->getCod(),
                'weight'           => $order->getWeight(),
                'length'           => $order->getLength(),
                'height'           => $order->getHeight(),
                'width'            => $order->getWidth(),
                'adult_content'    => $order->hasAdultContent() ?? false,
                'dispatch_at'      => $order->getPlannedDispatch(),
            ];
        }

        return $result;
    }
}
