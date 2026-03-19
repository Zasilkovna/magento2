<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\CarrierCountry\Listing;

use Magento\Directory\Model\ResourceModel\Country\CollectionFactory;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Sql\UnionExpression;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Packetery\Checkout\Model\Carrier\Facade;
use Packetery\Checkout\Ui\Component\CarrierCountry\Form\Modifier;
use Psr\Log\LoggerInterface as Logger;

class SearchResult extends \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult
{
    private Modifier $modifier;
    private Facade $carrierFacade;
    private CollectionFactory $countryCollectionFactory;

    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        Modifier $modifier,
        Facade $carrierFacade,
        CollectionFactory $countryCollectionFactory,
        string $mainTable,
        ?string $resourceModel = null,
        ?string $identifierName = null,
        ?string $connectionName = null
    ) {
        $this->carrierFacade = $carrierFacade;
        $this->modifier = $modifier;
        $this->countryCollectionFactory = $countryCollectionFactory;

        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $mainTable,
            $resourceModel,
            $identifierName,
            $connectionName
        );
    }

    protected function _initSelect()
    {
        $countries = $this->carrierFacade->getAllAvailableCountries(true);
        if (empty($countries)) {
            return parent::_initSelect();
        }

        $countryNames = [];
        foreach ($this->countryCollectionFactory->create()->addCountryCodeFilter($countries) as $countryCollectionRow) {
            $countryNames[$countryCollectionRow->getCountryId()] = $countryCollectionRow->getName();
        }

        $assembledQueries = [];
        $connection = $this->getConnection();
        foreach ($countries as $index => $code) {
            if (empty($this->modifier->getCarriers($code))) {
                continue;
            }

            $hasPricing = !empty($this->modifier->getPricingRulesForCountry($code, true));
            $qCountry = $connection->quote($code);
            $qName = $connection->quote($countryNames[$code] ?? $code);
            $qAvailable = $hasPricing ? '1' : '0';
            $qRank = (int) $index;

            $qiCountry = $connection->quoteIdentifier('country');
            $qiName = $connection->quoteIdentifier('country_name');
            $qiAvailable = $connection->quoteIdentifier('available');
            $qiRank = $connection->quoteIdentifier('rank');

            $assembledQueries[] = " (SELECT {$qCountry} AS {$qiCountry},
                {$qName} AS {$qiName},
                {$qAvailable} AS {$qiAvailable},
                {$qRank} AS {$qiRank}) ";
        }

        if (empty($assembledQueries)) {
            return parent::_initSelect();
        }

        $this->getSelect()
            ->from(['main_table' => new \Zend_Db_Expr('(' . new UnionExpression($assembledQueries, Select::SQL_UNION) . ')')])
            ->reset('columns')
            ->columns(
                [
                    'country',
                    'countryName' => 'country_name',
                    'available',
                    'rank',
                ]
            )
            ->group(
                [
                    'country',
                ]
            );

        $this->addFilterToMap('countryName', 'country_name');
        $this->addFilterToMap('availableName', 'available');

        return $this;
    }

    protected function _beforeLoad(): SearchResult
    {
        if (empty($this->_orders)) {
            $this->setOrder('rank', 'ASC');
        }
        return parent::_beforeLoad();
    }
}
