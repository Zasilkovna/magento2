<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Order;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Packetery\Checkout\Controller\Config\ShippingRatesConfig;
use Packetery\Checkout\Model\Carrier\Methods;
use Packetery\Checkout\Model\Carrier\ShippingRateCode;

class DataProvider extends AbstractDataProvider
{
    /** @var \Packetery\Checkout\Model\ResourceModel\Order\Collection */
    protected $collection;

    /** @var \Magento\Sales\Model\OrderFactory */
    private $orderFactory;

    /** @var \Packetery\Checkout\Model\Carrier\Facade */
    private $carrierFacade;

    /**
     * DataProvider constructor.
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $collectionFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Packetery\Checkout\Model\Carrier\Facade $carrierFacade
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $collectionFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Packetery\Checkout\Model\Carrier\Facade $carrierFacade,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
        $this->orderFactory = $orderFactory;
        $this->carrierFacade = $carrierFacade;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        $result = [];

        foreach ($this->collection->getItems() as $item) {
            $result[$item->getId()]['general'] = $item->getData(); // princing rules
            $orderNumber = $result[$item->getId()]['general']['order_number'];
            $order = $this->orderFactory->create()->loadByIncrementId($orderNumber);

            $shippingMethod = $order->getShippingMethod();
            if ($shippingMethod && ShippingRateCode::isPacketery($shippingMethod) && $order->getShippingAddress()) {
                $shippingRateCode = ShippingRateCode::fromString($shippingMethod);
                $methodCode = $shippingRateCode->getMethodCode();
                $result[$item->getId()]['general']['misc']['isPickupPointDelivery'] = (Methods::isPickupPointDelivery($methodCode->getMethod()) ? '1' : '0');
                $result[$item->getId()]['general']['misc']['isAnyAddressDelivery'] = (Methods::isAnyAddressDelivery($methodCode->getMethod()) ? '1' : '0');
                $widgetVendors = [];

                $carrier = $this->carrierFacade->getMagentoCarrier($shippingRateCode->getCarrierCode());
                $dynamicCarrier = $carrier->getPacketeryBrain()->getDynamicCarrierById($methodCode->getDynamicCarrierId());

                if ($carrier instanceof \Packetery\Checkout\Model\Carrier\Imp\Packetery\Carrier && $dynamicCarrier === null) {
                    $dynamicCarriers = $carrier->getPacketeryBrain()->findConfigurableDynamicCarriers($order->getShippingAddress()->getCountryId(), [$methodCode->getMethod()]);
                    $widgetVendors = ShippingRatesConfig::buildWidgetVendors(
                        $dynamicCarriers,
                        null
                    );
                }

                if ($dynamicCarrier !== null) {
                    $widgetVendors = ShippingRatesConfig::buildWidgetVendors(
                        [$dynamicCarrier],
                        null
                    );
                }

                $result[$item->getId()]['general']['misc']['widgetVendors'] = json_encode($widgetVendors, JSON_THROW_ON_ERROR);

            } else {
                $result[$item->getId()]['general']['misc']['isPickupPointDelivery'] = '0';
                $result[$item->getId()]['general']['misc']['isAnyAddressDelivery'] = '0';
                $result[$item->getId()]['general']['misc']['widgetVendors'] = '[]';
            }
        }

        return $result;
    }
}
