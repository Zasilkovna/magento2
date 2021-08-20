<?php

namespace Packetery\Checkout\Observer\Sales;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\InputException;
use Packetery\Checkout\Model\Carrier\AbstractBrain;
use Packetery\Checkout\Model\Carrier\MethodCode;
use Packetery\Checkout\Model\Carrier\Methods;

class OrderPlaceAfter implements \Magento\Framework\Event\ObserverInterface
{
    /** @var CheckoutSession */
    protected $checkoutSession;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    private $storeManager;

    /** @var \Packetery\Checkout\Model\Carrier\Imp\Packetery\Config */
    private $packeteryConfig;

    /** @var \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory */
    private $orderCollectionFactory;

    /** @var \Magento\Shipping\Model\CarrierFactory */
    private $carrierFactory;

    public function __construct(
        CheckoutSession $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Packetery\Checkout\Model\Carrier\Imp\Packetery\Carrier $packetery,
        \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Shipping\Model\CarrierFactory $carrierFactory
    ) {
        $this->storeManager = $storeManager;
        $this->checkoutSession = $checkoutSession;
        $this->packeteryConfig = $packetery->getPacketeryConfig();
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->carrierFactory = $carrierFactory;
    }

    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();

        // IF PACKETERY SHIPPING IS NOT SELECTED, RETURN
        if (strpos($order->getShippingMethod(), AbstractBrain::PREFIX) === false)
        {
            return;
        }

        // GET DATA
        $streetMatches = [];
        $match = preg_match('/^(.*[^0-9]+) (([1-9][0-9]*)\/)?([1-9][0-9]*[a-cA-C]?)$/', $order->getShippingAddress()->getStreet()[0], $streetMatches);

        if (!$match) {
            $houseNumber = null;
            $street = $order->getShippingAddress()->getStreet()[0];
        } elseif (!isset($streetMatches[4])) {
            $houseNumber = null;
            $street = $streetMatches[1];
        } else {
            $houseNumber = (!empty($streetMatches[3])) ? $streetMatches[3] . "/" . $streetMatches[4] : $streetMatches[4];
            $street = $streetMatches[1];
        }

        $weight = $this->getOrderWeight($order);

        $postData = json_decode(file_get_contents("php://input"));
        $pointId = NULL;
        $pointName = NULL;
        $point = NULL;
        $isCarrier = false;
        $carrierPickupPoint = null;

        if ($postData)
        {
            // new order from frontend
            $shippingMethod = $order->getShippingMethod(true);
            $deliveryMethod = MethodCode::fromString($shippingMethod['method']);
            if ($deliveryMethod->getMethod() === Methods::PICKUP_POINT_DELIVERY) {
                // pickup point delivery
                $point = $postData->packetery->point;
                $pointId = $point->pointId;
                $pointName = $point->name;
                $isCarrier = (bool)$point->carrierId;
                $carrierPickupPoint = ($point->carrierPickupPointId ?: null);
            } else {
                /** @var \Packetery\Checkout\Model\Carrier\AbstractCarrier $carrier */
                $carrier = $this->carrierFactory->create($shippingMethod['carrier_code']);
                $pointId = $carrier->getPacketeryBrain()->resolvePointId(
                    $deliveryMethod->getMethod(),
                    $order->getShippingAddress()->getCountryId(),
                    $carrier->getPacketeryBrain()->getDynamicCarrierById($deliveryMethod->getDynamicCarrierId())
                );

                $pointName = '';
            }
        }
        else
        {
            // creating order from admin
            $packetery = $this->getRealOrderPacketery($order);
            if (!empty($packetery)) {
                $pointId = $packetery['point_id'];
                $pointName = $packetery['point_name'];
                $isCarrier = (bool)$packetery['is_carrier'];
                $carrierPickupPoint = $packetery['carrier_pickup_point'];
            }
        }

        if (empty($pointId)) {
            throw new InputException(__('You must select pick-up point'));
        }

		$paymentMethod = $order->getPayment()->getMethod();

        $data = [
            'order_number' => $order->getIncrementId(),
            'recipient_firstname' => $order->getShippingAddress()->getFirstname(),
            'recipient_lastname' => $order->getShippingAddress()->getLastname(),
            'recipient_company' => $order->getShippingAddress()->getCompany(),
            'recipient_email' => $order->getShippingAddress()->getEmail(),
            'recipient_phone' => $order->getShippingAddress()->getTelephone(),
            'cod' => ($this->isCod($paymentMethod) ? $order->getGrandTotal() : 0),
            'currency' => $order->getOrderCurrencyCode(),
            'value' => $order->getGrandTotal(),
            'weight' => $weight,
            'point_id' => $pointId,
            'point_name' => $pointName,
            'is_carrier' => $isCarrier,
            'carrier_pickup_point' => $carrierPickupPoint,
            'sender_label' => $this->getLabel(),
            'recipient_street' => $street,
            'recipient_house_number' => $houseNumber,
            'recipient_city' => $order->getShippingAddress()->getCity(),
            'recipient_zip' => $order->getShippingAddress()->getPostcode(),
            'exported' => 0,
        ];

        $this->saveData($data);

    }

    /**
     * Returns total weight of ordered items.
     *
     * @param \Magento\Sales\Model\Order $order
     * @return float
     */
    private function getOrderWeight(\Magento\Sales\Model\Order $order): float {
        $productWeights = [];
        $totalWeight = 0.0;
        /** @var \Magento\Sales\Model\Order\Item[] $allVisibleItems */
        $allVisibleItems = $order->getAllVisibleItems();

        foreach ($allVisibleItems as $item) {
            if ($item->getProductType() === 'configurable') {
                $configurableProduct = $item->getProduct();
                if ($configurableProduct->isVirtual()) {
                    $configurableWeight = 0.0;
                } else {
                    $configurableWeight = $configurableProduct->getWeight();
                }

                /** @var \Magento\Sales\Model\Order\Item[] $children */
                $children = $item->getChildrenItems(); // contains only ordered items
                foreach ($children as $child) {
                    $childProduct = $child->getProduct();
                    if ($childProduct->isVirtual()) {
                        $productWeights[$childProduct->getId()] = 0.0;
                        continue;
                    }

                    if (is_numeric($childProduct->getWeight())) {
                        $childWeight = $childProduct->getWeight();
                    } else {
                        $childWeight = $configurableWeight;
                    }

                    $productWeights[$childProduct->getId()] = $childWeight * $item->getQtyOrdered();
                }
            }
        }

        foreach ($allVisibleItems as $item) {
            $product = $item->getProduct();
            if ($item->getProductType() === 'simple' && !array_key_exists($product->getId(), $productWeights)) {
                if ($product->isVirtual()) {
                    $productWeights[$product->getId()] = 0.0;
                    continue;
                }

                $productWeights[$product->getId()] = $product->getWeight() * $item->getQtyOrdered();
            }
        }

        foreach ($productWeights as $itemWeight) {
            $totalWeight += (float)$itemWeight;
        }

        return $totalWeight;
    }

    private function getRealOrderPacketery($order)
    {
        $orderIdOriginal = self::getRealOrderId($order->getIncrementId());
        if (!is_numeric($orderIdOriginal))
        {
            return null;
        }

        $collection = $this->orderCollectionFactory->create();
        $collection->addFilter('order_number', $orderIdOriginal);
        $collection->load();
        $item = $collection->fetchItem();

        if (empty($item)) {
            return null;
        }

        $data = $item->toArray(['point_id', 'point_name', 'is_carrier', 'carrier_pickup_point']);

        if (empty($data))
        {
            return null;
        }

        return $data;
    }

    private static function getRealOrderId($orderId)
    {
        // $orderId = ltrim($orderId, 0);
        $orderId = strstr($orderId, "-", TRUE);

        return $orderId;
    }

	/**
	 * Check, if it is COD type in Packetery configuration
	 */
	private function isCod($methodCode)
	{
        $codPayments = $this->packeteryConfig->getCodMethods();
		return in_array($methodCode, $codPayments);
	}

	/**
	 * Create unique label/id of the store
	 */
	private function getLabel()
	{
        $store = $this->storeManager->getGroup();

        if($store)
        {
            return $store->getCode();
        }
        return null;
    }

	/**
	 * Save order data to packetery module
	 * @package array $data
	 */
	private function saveData(array $data): void
	{
        /** @var \Packetery\Checkout\Model\ResourceModel\Order\Collection $collection */
        $collection = $this->orderCollectionFactory->create();
        $order = $collection->getNewEmptyItem();
        $order->setData($data);
        $collection->addItem($order);
        $collection->save();
	}
}
