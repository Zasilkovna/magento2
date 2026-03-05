<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\Order\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Packetery\Checkout\Model\Carrier\ShippingRateCode;

class DeliveryDestination extends Column
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
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array {
        $cache = [];

        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $shippingRate = ShippingRateCode::fromString($item['shipping_rate_code']);
                $isFeedCarrier = $shippingRate->getCarrierCode() === \Packetery\Checkout\Model\Carrier\Imp\PacketeryPacketaDynamic\Brain::getCarrierCodeStatic();
                if ($isFeedCarrier) {
                    $methodCode = $shippingRate->getMethodCode();
                    $carrier = $this->carrierFacade->createHybridCarrierCached($cache, $shippingRate->getCarrierCode(), $methodCode->getDynamicCarrierId(), $methodCode->getMethod(), '');
                    $item[$this->getData('name')] = $carrier->getFinalCarrierName();
                } else {
                    $item[$this->getData('name')] = (string)$item['point_name'];
                }
            }
        }

        return $dataSource;
    }

    protected function applySorting() {
        // no DB select sorting
    }
}
