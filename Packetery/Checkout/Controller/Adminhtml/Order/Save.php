<?php

declare(strict_types=1);

namespace Packetery\Checkout\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Locale\FormatInterface;
use Psr\Log\LoggerInterface;

class Save extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Packetery_Checkout::packetery';

    /** @var \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory */
    private $orderCollectionFactory;

    /** @var \Magento\Sales\Model\OrderFactory */
    private $orderFactory;

    /** @var \Packetery\Checkout\Model\PacketRepository */
    private $packetRepository;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /** @var \Magento\Framework\Locale\FormatInterface */
    private $localeFormat;

    public function __construct(
        Context $context,
        \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Packetery\Checkout\Model\PacketRepository $packetRepository,
        LoggerInterface $logger,
        FormatInterface $localeFormat
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->orderFactory = $orderFactory;
        $this->packetRepository = $packetRepository;
        $this->logger = $logger;
        $this->localeFormat = $localeFormat;

        parent::__construct($context);
    }

    /**
     * Keeps a literal "0" (e.g. recipient_house_number "0");
     * only a missing or empty value falls back to $default,
     * so an empty mirror field for a nullable numeric column
     * (recipient_longitude/latitude) persists as $default (null), not "".
     */
    private function getDataItem(array $data, string $key, mixed $default): mixed
    {
        if (!array_key_exists($key, $data)) {
            return $default;
        }

        $value = $data[$key];
        if ($value === '' || $value === null) {
            return $default;
        }

        return $value;
    }

    /**
     * Parses raw form input to an optional number: empty stays null, otherwise the locale-aware core parser
     */
    private function parseOptionalNumber(mixed $raw): ?float
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        return $this->localeFormat->getNumber((string) $raw);
    }

    /**
     * The delivery type comes from the order's real shipping method, not the posted form flag,
     * so a tampered request can't write pickup-point fields onto an address order or the reverse
     */
    private function isPickupPointDelivery(\Magento\Sales\Model\Order $order): bool
    {
        $shippingMethod = (string) $order->getShippingMethod();
        if (!\Packetery\Checkout\Model\Carrier\ShippingRateCode::isPacketery($shippingMethod)) {
            return false;
        }

        $method = \Packetery\Checkout\Model\Carrier\ShippingRateCode::fromString($shippingMethod)
            ->getMethodCode()
            ->getMethod();

        return \Packetery\Checkout\Model\Carrier\Methods::isPickupPointDelivery($method);
    }

    /**
     * Redirects to the native order view, or the Packeta order list when the order can't be resolved
     */
    private function redirectToOrder(Redirect $redirect, ?string $orderNumber): Redirect
    {
        if ($orderNumber !== null) {
            $magentoOrder = $this->orderFactory->create()->loadByIncrementId($orderNumber);
            if ($magentoOrder->getId()) {
                return $redirect->setPath('sales/order/view', ['order_id' => $magentoOrder->getId()]);
            }
        }

        return $redirect->setPath('packetery/order/index');
    }

    /**
     * @return Redirect
     */
    public function execute(): Redirect
    {
        if (!$this->getRequest()->isPost()) {
            throw new NotFoundException(__('Page not found'));
        }

        $postData = $this->getRequest()->getPostValue()['general'] ?? null;
        if (!is_array($postData) || !isset($postData['id'])) {
            throw new NotFoundException(__('Page not found'));
        }

        $id = (int) $postData['id'];

        $collection = $this->orderCollectionFactory->create();
        $collection->addFilter('id', $id);

        // No row for this id means a tampered or stale form, so reject instead of silently reporting "Saved"
        if (!$collection->getFirstItem()->getId()) {
            throw new NotFoundException(__('Page not found'));
        }

        /** @var Redirect $redirect */
        $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $orderNumber = $collection->getFirstItem()->getOrderNumber();

        // The packet is the source of truth once submitted; editing the order row afterwards would
        // desync it from the packet already sent to Packeta. The form is hidden once submitted, so a
        // POST here is a stale tab or a tampered request and is rejected like the missing-row guard
        if ($this->packetRepository->findLatestByOrderNumber($orderNumber) !== null) {
            $this->messageManager->addErrorMessage(__('Could not save packet details.'));

            return $this->redirectToOrder($redirect, $orderNumber);
        }

        $numericValues = [];
        foreach (['value', 'cod', 'weight'] as $field) {
            $number = $this->parseOptionalNumber($postData[$field] ?? null);
            // The form blocks negative input client-side, so a negative value here is a tampered request
            if ($number !== null && $number < 0.0) {
                $this->messageManager->addErrorMessage(__('Could not save packet details.'));

                return $this->redirectToOrder($redirect, $orderNumber);
            }

            $numericValues[$field] = $number;
        }

        $magentoOrder = $this->orderFactory->create()->loadByIncrementId((string) $orderNumber);

        if ($this->isPickupPointDelivery($magentoOrder)) {
            $collection->setDataToAll(
                [
                    'point_id' => $this->getDataItem($postData, 'point_id', null),
                    'point_name' => $this->getDataItem($postData, 'point_name', null),
                    'is_carrier' => (bool) $this->getDataItem($postData, 'is_carrier', false),
                    'carrier_pickup_point' => $this->getDataItem($postData, 'carrier_pickup_point', null),
                ]
            );
        } else {
            // Address-delivery orders show the editable address picker, so persist the recipient address
            $collection->setDataToAll(
                [
                    'recipient_street' => $this->getDataItem($postData, 'recipient_street', null),
                    'recipient_house_number' => $this->getDataItem($postData, 'recipient_house_number', null),
                    'recipient_country_id' => $this->getDataItem($postData, 'recipient_country_id', null),
                    'recipient_county' => $this->getDataItem($postData, 'recipient_county', null),
                    'recipient_city' => $this->getDataItem($postData, 'recipient_city', null),
                    'recipient_zip' => $this->getDataItem($postData, 'recipient_zip', null),
                    'recipient_longitude' => $this->getDataItem($postData, 'recipient_longitude', null),
                    'recipient_latitude' => $this->getDataItem($postData, 'recipient_latitude', null),
                ]
            );
        }

        // A soft-deleted box stays assigned on purpose so its dimensions are not lost; re-picking one
        // is blocked client-side, so the server only casts the value. A dangling box id is rejected
        // by the box_id foreign key and surfaces as the generic save error below
        $boxIdRaw = $postData['box_id'] ?? '';
        if ($boxIdRaw === '') {
            $boxId = null;
        } else {
            $boxId = (int) $boxIdRaw;
        }

        $collection->setDataToAll(
            array_merge(
                $numericValues,
                [
                    'adult_content' => empty($postData['adult_content']) ? 0 : 1,
                    'box_id' => $boxId,
                ]
            )
        );

        try {
            $collection->save();
        } catch (\Exception $e) {
            $this->logger->error('Packetery: failed to save order detail: ' . $e->getMessage());
            $this->messageManager->addErrorMessage(__('Could not save packet details.'));

            return $this->redirectToOrder($redirect, $orderNumber);
        }

        $this->messageManager->addSuccessMessage(__('Saved'));

        return $this->redirectToOrder($redirect, $orderNumber);
    }
}
