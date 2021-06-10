<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\CarrierCountry\Listing;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Psr\Log\LoggerInterface as Logger;

class SearchResult extends \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult
{
    /** @var \Packetery\Checkout\Ui\Component\CarrierCountry\Form\Modifier */
    private $modifier;

    /** @var \Magento\Framework\App\RequestInterface */
    private $request;

    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        \Packetery\Checkout\Ui\Component\CarrierCountry\Form\Modifier $modifier,
        \Magento\Framework\App\RequestInterface $request,
        $mainTable,
        $resourceModel = null,
        $identifierName = null,
        $connectionName = null
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel, $identifierName, $connectionName);
        $this->modifier = $modifier;
        $this->request = $request;
    }

    /**
     * @param string|array $field
     * @param null|string|array $condition
     * @return $this
     */
    public function addFieldToFilter($field, $condition = null) {
        if ($field === 'availableName') {
            return $this;
        }

        if (is_array($field) && in_array('availableName', $field)) {
            unset($field[array_search('availableName', $field)]);
            unset($condition[array_search('availableName', $field)]);

            if (empty($field)) {
                return $this;
            }
        }

        return parent::addFieldToFilter($field, $condition);
    }

    protected function _initSelect() {
        $packeteryCarrierTable = $this->getTable('packetery_carrier');
        $this->getSelect()
            ->from(['main_table' => $packeteryCarrierTable])
            ->columns(
                [
                    'country',
                ]
            )
            ->group(
                [
                    'country',
                ]
            );

        return $this;
    }

    protected function _afterLoadData() {
        parent::_afterLoadData();

        $filters = $this->request->getParam('filters', []);

        foreach ($this->_data as $key => &$item) {

            $options = $this->modifier->getCarriers($item['country']);

            if (!empty($options)) {
                $item['available'] = '1';
            } else {
                $item['available'] = '0';
            }

            if (isset($filters['availableName'])) {
                $value = $filters['availableName'];

                if ($item['available'] !== $value) {
                    unset($this->_data[$key]);
                }
            }
        }

        return $this;
    }
}
