<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\CarrierCountry\Listing\Column;

use Magento\Directory\Model\CountryFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Country extends Column
{
    /** @var \Magento\Directory\Model\CountryFactory  */
    protected $_countryFactory;

    /**
     * Country constructor.
     *
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context
     * @param \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        CountryFactory $countryFactory,
        array $components = [],
        array $data = []
    ) {
        $this->_countryFactory = $countryFactory;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $country = $this->_countryFactory->create()->loadByCode(strtoupper($item["country"]));
                $item[$this->getData('name')] = $country->getName();
            }

            $sorting = $this->getContext()->getRequestParam('sorting');
            $isSortable = $this->getData('config/sortable');
            if ($isSortable !== false
                && !empty($sorting['field'])
                && !empty($sorting['direction'])
                && $sorting['field'] === $this->getName()
                && in_array(strtoupper($sorting['direction']), ['ASC', 'DESC'], true)
            ) {
                if (strtoupper($sorting['direction']) === 'ASC') {
                    usort($dataSource['data']['items'], function ($a, $b) {
                        return strcmp($a[$this->getData('name')], $b[$this->getData('name')]);
                    });
                }

                if (strtoupper($sorting['direction']) === 'DESC') {
                    usort($dataSource['data']['items'], function ($a, $b) {
                        return strcmp($b[$this->getData('name')], $a[$this->getData('name')]);
                    });
                }
            }
        }

        return $dataSource;
    }

    protected function applySorting() {
        // no DB select sorting
    }

}
