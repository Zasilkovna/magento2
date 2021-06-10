<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\CarrierCountry\Listing\Column;

use Magento\Directory\Model\CountryFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Available extends Column
{
    /** @var \Magento\Directory\Model\CountryFactory */
    protected $countryFactory;

    /** @var \Packetery\Checkout\Ui\Component\CarrierCountry\Form\Modifier */
    private $modifier;

    /**
     * Country constructor.
     *
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context
     * @param \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     * @param \Packetery\Checkout\Ui\Component\CarrierCountry\Form\Modifier $modifier
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        CountryFactory $countryFactory,
        \Packetery\Checkout\Ui\Component\CarrierCountry\Form\Modifier $modifier,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->countryFactory = $countryFactory;
        $this->modifier = $modifier;
    }

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {

                $options = $this->modifier->getCarriers($item['country']);

                if (!empty($options)) {
                    $item['available'] = '1';
                    $item[$this->getData('name')] = __('Yes');
                } else {
                    $item['available'] = '0';
                    $item[$this->getData('name')] = __('No');
                }
            }
        }

        return $dataSource;
    }

    protected function applySorting() {
        // no DB select sorting
    }
}
