<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\Pricingrule\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Packetery\Checkout\Model\Carrier\Methods;

class PricingRuleEnabled extends Column
{
    /** @var \Packetery\Checkout\Model\Carrier\Facade */
    private $carrierFacade;

    /**
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context
     * @param \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory
     * @param \Packetery\Checkout\Model\Carrier\Facade $carrierFacade
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Packetery\Checkout\Model\Carrier\Facade $carrierFacade,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->carrierFacade = $carrierFacade;
    }

    /**
     * @param string $method
     * @return \Magento\Framework\Phrase
     */
    private function createCellContent(string $method, string $countryId): \Magento\Framework\Phrase
    {
        $activeCarriers = $this->carrierFacade->getActiveCarriers();
        foreach ($activeCarriers as $carrier) {
            $config = $carrier->getPacketeryConfig();
            $brain = $carrier->getPacketeryBrain();
            $methodAllowed = $brain->getFinalAllowedMethods($config, $carrier->getPacketeryBrain()->getMethodSelect())->hasAllowed($method);

            if ($method === Methods::PICKUP_POINT_DELIVERY) {
                $pointIdResolves = true;
            } else {
                $pointIdResolves = $brain->resolvePointId($method, $countryId) !== null;
            }

            if ($methodAllowed && $pointIdResolves) {
                return __('Enabled'); // if there is min 1 carrier, print Enabled
            }
        }

        return __('Disabled');
    }

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $item[$this->getData('name')] = $this->createCellContent($item["method"], $item["country_id"]);
            }
        }

        return $dataSource;
    }
}
