<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\Pricingrule\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;
use Packetery\Checkout\Model\Carrier\Config\AllowedMethods;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;

class PricingRuleEnabled extends Column
{
    /** @var \Packetery\Checkout\Model\Carrier\PacketeryConfig */
    protected $packeteryConfig;

    /**
     * MaxWeight constructor.
     *
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context
     * @param \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory
     * @param \Packetery\Checkout\Model\Carrier\PacketeryConfig $packeteryConfig
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Packetery\Checkout\Model\Carrier\PacketeryConfig $packeteryConfig,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->packeteryConfig = $packeteryConfig;
    }

    /**
     * @param string $method
     * @return \Magento\Framework\Phrase|null
     */
    private function createCellContent(string $method, string $countryId): ?\Magento\Framework\Phrase
    {
        $methodAllowed = $this->packeteryConfig->getAllowedMethods()->hasAllowed($method);
        $countryAllowed = $this->packeteryConfig->hasSpecificCountryAllowed($countryId);

        $suffix = null;
        if ($countryAllowed && $methodAllowed) {
            $suffix = __('Enabled');
        } else {
            $suffix = __('Disabled');
        }

        return $suffix;
    }

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {

                $phrase = null;
                switch ($item["method"]) {
                    case AllowedMethods::PICKUP_POINT_DELIVERY:
                        $phrase = $this->createCellContent(AllowedMethods::PICKUP_POINT_DELIVERY, $item["country_id"]);
                        break;
                    case AllowedMethods::ADDRESS_DELIVERY:
                        $phrase = $this->createCellContent(AllowedMethods::ADDRESS_DELIVERY, $item["country_id"]);
                        break;
                }

                $item[$this->getData('name')] = ($phrase !== null ? $phrase : $item["method"]);
            }
        }

        return $dataSource;
    }
}
