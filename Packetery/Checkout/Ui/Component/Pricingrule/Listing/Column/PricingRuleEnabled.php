<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\Pricingrule\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Packetery\Checkout\Model\Carrier\AbstractCarrier;
use Packetery\Checkout\Model\Carrier\Methods;

class PricingRuleEnabled extends Column
{
    /** @var \Magento\Shipping\Model\Config */
    private $shippingConfig;

    /**
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context
     * @param \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory
     * @param \Magento\Shipping\Model\Config $shippingConfig
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Magento\Shipping\Model\Config $shippingConfig,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->shippingConfig = $shippingConfig;
    }

    /**
     * @param string $method
     * @param string $countryId
     * @param string $carrierCode
     * @param int|null $carrierId
     * @return \Magento\Framework\Phrase
     */
    private function createCellContent(string $method, string $countryId, string $carrierCode, ?int $carrierId): \Magento\Framework\Phrase
    {
        foreach ($this->shippingConfig->getActiveCarriers() as $carrier) {
            if (!$carrier instanceof AbstractCarrier) {
                continue;
            }

            $config = $carrier->getPacketeryConfig();
            $brain = $carrier->getPacketeryBrain();
            $methodAllowed = in_array($method, $brain->getFinalAllowedMethods($config, $carrier->getPacketeryBrain()->getMethodSelect()));

            if ($method === Methods::PICKUP_POINT_DELIVERY) {
                $pointIdResolves = true;
            } else {
                $pointIdResolves = $brain->resolvePointId($method, $countryId) !== null; // todo pass carrier_id
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
                $item[$this->getData('name')] = $this->createCellContent($item["method"], $item["country_id"], $item["carrier_code"], $item["carrier_id"]);
            }
        }

        return $dataSource;
    }
}
