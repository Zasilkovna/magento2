<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\Box;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Packetery\Checkout\Model\Box;
use Packetery\Checkout\Model\ResourceModel\Box\CollectionFactory;

class DataProvider extends AbstractDataProvider
{
    protected array $loadedData;

    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $collectionFactory,
        private readonly DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();

        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $meta,
            $data
        );
    }

    public function getData(): array
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $this->loadedData = [];
        $items = $this->collection->getItems();
        foreach ($items as $box) {
            $this->loadedData[$box->getId()] = $box->getData();
        }

        $data = $this->dataPersistor->get(Box::TABLE_NAME);
        if (!empty($data)) {
            $box = $this->collection->getNewEmptyItem();
            $box->setData($data);
            $this->loadedData[$box->getId()] = $box->getData();
            $this->dataPersistor->clear(Box::TABLE_NAME);
        }

        return $this->loadedData;
    }
}
