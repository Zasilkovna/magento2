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

    /** @var \Packetery\Checkout\Model\Carrier\Imp\Packetery\Carrier */
    private $packeteryCarrier;

    /** @var \Packetery\Checkout\Model\Carrier\Facade */
    private $carrierFacade;

    /**
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Packetery\Checkout\Model\Carrier\Imp\Packetery\Carrier $packeteryCarrier
     * @param \Packetery\Checkout\Ui\Component\CarrierCountry\Form\Modifier $modifier
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Packetery\Checkout\Model\Carrier\Facade $carrierFacade
     * @param $mainTable
     * @param null $resourceModel
     * @param null $identifierName
     * @param null $connectionName
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        \Packetery\Checkout\Model\Carrier\Imp\Packetery\Carrier $packeteryCarrier,
        \Packetery\Checkout\Ui\Component\CarrierCountry\Form\Modifier $modifier,
        \Magento\Framework\App\RequestInterface $request,
        \Packetery\Checkout\Model\Carrier\Facade $carrierFacade,
        $mainTable,
        $resourceModel = null,
        $identifierName = null,
        $connectionName = null
    ) {
        $this->carrierFacade = $carrierFacade;
        $this->packeteryCarrier = $packeteryCarrier;
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
        $neededCountries = $this->carrierFacade->getAllAvailableCountries();
        $assembledQueries = [];

        foreach ($neededCountries as $neededCountry) {
            $neededCountry = (new \Zend_Db_Select($this->getSelect()->getAdapter()))
                ->from(['main_table' => $this->getTable('setup_module')])
                ->reset('columns')
                ->columns(
                    [
                        'country' => new \Zend_Db_Expr("'{$neededCountry}'"),
                    ]
                );

            $assembledQueries[] = $neededCountry->assemble();
        }

        $this->getSelect()
            ->from(['main_table' => new \Zend_Db_Expr('(' . implode(' UNION ALL ', $assembledQueries) . ')')])
            ->reset('columns')
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
