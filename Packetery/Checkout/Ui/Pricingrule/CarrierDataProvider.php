<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Pricingrule;

use Magento\Ui\DataProvider\AbstractDataProvider;

class CarrierDataProvider extends AbstractDataProvider
{
    /** @var \Packetery\Checkout\Model\ResourceModel\Pricingrule\Collection */
    protected $collection;

    /** @var \Packetery\Checkout\Model\ResourceModel\Weightrule\CollectionFactory */
    protected $weightRuleCollectionFactory;

    /** @var \Packetery\Checkout\Ui\Component\CarrierCountry\Form\Modifier */
    private $modifier;

    /**
     * DataProvider constructor.
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param \Packetery\Checkout\Model\ResourceModel\Pricingrule\CollectionFactory $collectionFactory
     * @param \Packetery\Checkout\Model\ResourceModel\Weightrule\CollectionFactory $weightRuleCollectionFactory
     * @param \Packetery\Checkout\Ui\Component\CarrierCountry\Form\Modifier $modifier
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        \Packetery\Checkout\Model\ResourceModel\Pricingrule\CollectionFactory $collectionFactory,
        \Packetery\Checkout\Model\ResourceModel\Weightrule\CollectionFactory $weightRuleCollectionFactory,
        \Packetery\Checkout\Ui\Component\CarrierCountry\Form\Modifier $modifier,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
        $this->weightRuleCollectionFactory = $weightRuleCollectionFactory;
        $this->modifier = $modifier;
    }

    /**
     * @return array
     */
    public function getData(): array {
        $result = [];

        // todo pair pricing rule with carrier
        // todo iterate carriers

        foreach ($this->collection->getItems() as $item) {
            $result[$item->getId()]['general'] = $item->getData(); // princing rules

            $result[$item->getId()]['general']['weightRules'] = [];
            $result[$item->getId()]['general']['weightRules']['weightRules'] = []; // magento renders data in such structure

            /** @var \Packetery\Checkout\Model\ResourceModel\Weightrule\Collection $weightRuleCollection */
            $weightRuleCollection = $this->weightRuleCollectionFactory->create();
            $weightRuleCollection->addFilter('packetery_pricing_rule_id', $item->getId());
            $weightRules = $weightRuleCollection->getItems();
            foreach ($weightRules as $weightRule) {
                $result[$item->getId()]['general']['weightRules']['weightRules'][] = $weightRule->getData(); // must use natural array keys
            }
        }

        return $result;
    }

    public function getMeta() {
        return $this->modifier->modifyMeta(parent::getMeta());
    }
}
