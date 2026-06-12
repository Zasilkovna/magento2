<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Order;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Packetery\Checkout\Controller\Config\ShippingRatesConfig;
use Packetery\Checkout\Model\AddressValidationResolver;
use Packetery\Checkout\Model\AdultContentResolver;
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

    /** @var \Packetery\Checkout\Model\PacketRepository */
    private $packetRepository;

    /** @var \Magento\Framework\App\RequestInterface */
    private $request;

    /** @var \Magento\Sales\Api\OrderRepositoryInterface */
    private $orderRepository;

    /** @var \Packetery\Checkout\Model\BoxRepository */
    private $boxRepository;

    /** @var \Packetery\Checkout\Model\OrderCurrencyResolver */
    private $orderCurrencyResolver;

    /** @var \Magento\Sales\Api\Data\OrderInterface|null */
    private $magentoOrder;

    /** @var bool */
    private $magentoOrderLoaded = false;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $collectionFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Packetery\Checkout\Model\Carrier\Facade $carrierFacade
     * @param \Packetery\Checkout\Model\PacketRepository $packetRepository
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Packetery\Checkout\Model\BoxRepository $boxRepository
     * @param \Packetery\Checkout\Model\OrderCurrencyResolver $orderCurrencyResolver
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
        \Packetery\Checkout\Model\PacketRepository $packetRepository,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Packetery\Checkout\Model\BoxRepository $boxRepository,
        \Packetery\Checkout\Model\OrderCurrencyResolver $orderCurrencyResolver,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
        $this->orderFactory = $orderFactory;
        $this->carrierFacade = $carrierFacade;
        $this->packetRepository = $packetRepository;
        $this->request = $request;
        $this->orderRepository = $orderRepository;
        $this->boxRepository = $boxRepository;
        $this->orderCurrencyResolver = $orderCurrencyResolver;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        $result = [];

        foreach ($this->collection->getItems() as $item) {
            $result[$item->getId()]['general'] = $item->getData();
            $orderNumber = $result[$item->getId()]['general']['order_number'];
            $packet = $this->packetRepository->findLatestByOrderNumber($orderNumber);
            $consignPassword = $packet === null ? null : $packet->getConsignPassword();
            $result[$item->getId()]['general']['consign_password'] = $consignPassword;
            $order = $this->orderFactory->create()->loadByIncrementId($orderNumber);

            $packeteryConfig = $this->carrierFacade->getPacketeryCarrierConfig((int) $order->getStoreId());
            $isShowConsignPassword = $packeteryConfig !== null && $packeteryConfig->isShowConsignPassword();
            $showConsignPassword = $isShowConsignPassword && $consignPassword !== null;
            $result[$item->getId()]['general']['misc']['showConsignPassword'] = $showConsignPassword ? '1' : '0';

            $defaultWeight = $this->resolveDefaultWeightPrefill($item->getWeight(), $packeteryConfig);
            if ($defaultWeight !== null) {
                $result[$item->getId()]['general']['weight'] = $defaultWeight;
            }

            $shippingMethod = $order->getShippingMethod();
            if ($shippingMethod && ShippingRateCode::isPacketery($shippingMethod) && $order->getShippingAddress()) {
                $shippingRateCode = ShippingRateCode::fromString($shippingMethod);
                $methodCode = $shippingRateCode->getMethodCode();
                $method = $methodCode->getMethod();
                $countryId = (string) $order->getShippingAddress()->getCountryId();
                $isPickupPointDelivery = Methods::isPickupPointDelivery($method);

                $result[$item->getId()]['general']['misc']['isPickupPointDelivery'] = ($isPickupPointDelivery ? '1' : '0');
                $result[$item->getId()]['general']['misc']['isAdultContentEligible'] = (AdultContentResolver::isEligibleForAdultContent($method, $countryId, $item->isCarrier()) ? '1' : '0');
                $result[$item->getId()]['general']['misc']['isAddressValidationEligible'] = (AddressValidationResolver::isEligibleForAddressValidation($method, $countryId) ? '1' : '0');

                $carrier = $this->carrierFacade->getMagentoCarrier($shippingRateCode->getCarrierCode());
                $dynamicCarrier = $carrier->getPacketeryBrain()->getDynamicCarrierById($methodCode->getDynamicCarrierId());

                $result[$item->getId()]['general']['misc']['isCodEligible'] = ($this->isCodEligible($item->getCod(), $dynamicCarrier) ? '1' : '0');

                $requiresSize = $dynamicCarrier !== null && $dynamicCarrier->requiresSize();
                $result[$item->getId()]['general']['misc']['isSizeEligible'] = ($requiresSize ? '1' : '0');

                if ($requiresSize && $packet === null) {
                    $defaultBoxId = $this->resolveDefaultBoxPrefill($item->getBoxId());
                    if ($defaultBoxId !== null) {
                        $result[$item->getId()]['general']['box_id'] = $defaultBoxId;
                    }
                }

                $widgetVendors = [];
                if ($isPickupPointDelivery) {
                    if ($carrier instanceof \Packetery\Checkout\Model\Carrier\Imp\Packetery\Carrier && $dynamicCarrier === null) {
                        $dynamicCarriers = $carrier->getPacketeryBrain()->findConfigurableDynamicCarriers($order->getShippingAddress()->getCountryId(), [$method]);
                        $widgetVendors = ShippingRatesConfig::createWidgetVendors(
                            $dynamicCarriers,
                            null
                        );
                    }

                    if ($dynamicCarrier !== null) {
                        $widgetVendors = ShippingRatesConfig::createWidgetVendors(
                            [$dynamicCarrier],
                            null
                        );
                    }
                }

                $result[$item->getId()]['general']['misc']['widgetVendors'] = json_encode($widgetVendors, JSON_THROW_ON_ERROR);
            } else {
                $result[$item->getId()]['general']['misc']['isPickupPointDelivery'] = '0';
                $result[$item->getId()]['general']['misc']['isAdultContentEligible'] = '0';
                $result[$item->getId()]['general']['misc']['isAddressValidationEligible'] = '0';
                $result[$item->getId()]['general']['misc']['isCodEligible'] = '0';
                $result[$item->getId()]['general']['misc']['isSizeEligible'] = '0';
                $result[$item->getId()]['general']['misc']['widgetVendors'] = '[]';
            }
        }

        return $result;
    }

    /**
     * Injects per-order bits the static form XML can't express: currency suffix on the value/cod fields
     */
    public function getMeta(): array
    {
        $meta = parent::getMeta();

        $item = $this->resolveOrderItem();
        $order = $this->resolveMagentoOrder();

        if (
            $item !== null
            && $order !== null
            && !$this->isDeliverySectionVisible($order)
        ) {
            $meta = array_replace_recursive(
                $meta,
                [
                    'general' => [
                        'children' => [
                            'delivery' => [
                                'arguments' => [
                                    'data' => [
                                        'config' => [
                                            'visible' => false,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            );
        }

        $currency = $this->orderCurrencyResolver->resolve($item, $order);
        if ($currency !== null) {
            $suffix = [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'addafter' => $currency,
                        ]
                    ]
                ]
            ];

            $meta = array_replace_recursive(
                $meta,
                [
                    'general' => [
                        'children' => [
                            'packet' => [
                                'children' => [
                                    'value' => $suffix,
                                    'cod' => $suffix,
                                ],
                            ],
                        ],
                    ],
                ]
            );
        }

        return $meta;
    }

    private function resolveOrderItem(): ?\Packetery\Checkout\Model\Order
    {
        $order = $this->resolveMagentoOrder();
        if ($order === null) {
            return null;
        }

        $orderNumber = $order->getIncrementId();

        /** @var \Packetery\Checkout\Model\Order $item */
        foreach ($this->collection->getItems() as $item) {
            if ($item->getOrderNumber() === $orderNumber) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Loads the Magento order from the order_id request param. Cached (getMeta resolves it twice).
     */
    private function resolveMagentoOrder(): ?\Magento\Sales\Api\Data\OrderInterface
    {
        if ($this->magentoOrderLoaded) {
            return $this->magentoOrder;
        }

        $this->magentoOrderLoaded = true;

        $orderId = (int) $this->request->getParam('order_id');
        if ($orderId > 0) {
            try {
                $this->magentoOrder = $this->orderRepository->get($orderId);
            } catch (\Magento\Framework\Exception\NoSuchEntityException) {
                $this->magentoOrder = null;
            }
        }

        return $this->magentoOrder;
    }

    /**
     * Store-default weight prefill (display only) when the order has none; string to match the decimal column, else null
     */
    private function resolveDefaultWeightPrefill(
        ?float $storedWeight,
        ?\Packetery\Checkout\Model\Carrier\Imp\Packetery\Config $packeteryConfig
    ): ?string {
        if (($storedWeight !== null && $storedWeight > 0.0) || $packeteryConfig === null) {
            return null;
        }

        $defaultWeight = $packeteryConfig->getDefaultWeight();
        if ($defaultWeight === null || $defaultWeight <= 0.0) {
            return null;
        }

        return (string) $defaultWeight;
    }

    /**
     * Store-default box prefill (display only) when none is chosen; null when a box is set or no default exists
     */
    private function resolveDefaultBoxPrefill(?int $currentBoxId): ?int
    {
        if ($currentBoxId !== null && $currentBoxId > 0) {
            return null;
        }

        try {
            $defaultBox = $this->boxRepository->getDefaultBox();
        } catch (\Magento\Framework\Exception\NoSuchEntityException) {
            return null;
        }

        return $defaultBox !== null ? (int) $defaultBox->getId() : null;
    }

    /**
     * The Delivery section holds only the pickup-point picker and the address picker; with neither offered
     * (home delivery outside the address-validation countries) it would render as a bare heading, so hide it
     */
    private function isDeliverySectionVisible(\Magento\Sales\Api\Data\OrderInterface $order): bool
    {
        $shippingMethod = $order->getShippingMethod();
        if (!$shippingMethod || !ShippingRateCode::isPacketery($shippingMethod) || !$order->getShippingAddress()) {
            return false;
        }

        $method = ShippingRateCode::fromString($shippingMethod)->getMethodCode()->getMethod();
        $countryId = (string) $order->getShippingAddress()->getCountryId();

        return Methods::isPickupPointDelivery($method)
            || AddressValidationResolver::isEligibleForAddressValidation($method, $countryId);
    }

    /**
     * COD field shows only for a COD order (cod > 0) on a carrier that allows cash on delivery
     */
    private function isCodEligible(?float $cod, ?\Packetery\Checkout\Model\Carrier\AbstractDynamicCarrier $dynamicCarrier): bool
    {
        $isCodOrder = $cod !== null && $cod > 0.0;
        $disallowsCod = $dynamicCarrier !== null && $dynamicCarrier->disallowsCod();

        return $isCodOrder && $disallowsCod === false;
    }
}
