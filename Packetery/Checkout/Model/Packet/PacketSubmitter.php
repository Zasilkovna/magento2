<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Packet;

use Packetery\Checkout\Model\Carrier\Methods;
use Packetery\Checkout\Model\Carrier\ShippingRateCode;
use Packetery\Checkout\Model\Dimensions\Unit;

class PacketSubmitter
{
    /** @var \Packetery\Checkout\Model\Api\SoapApiClient */
    private $soapApiClient;

    /** @var \Packetery\Checkout\Model\Weight\Calculator */
    private $weightCalculator;

    /** @var \Packetery\Checkout\Model\PacketFactory */
    private $packetFactory;

    /** @var \Packetery\Checkout\Model\Packet\SubmitPreconditions */
    private $submitPreconditions;

    /** @var \Packetery\Checkout\Model\ResourceModel\Packet */
    private $packetResource;

    /** @var \Packetery\Checkout\Model\ResourceModel\Order */
    private $orderResource;

    /** @var \Packetery\Checkout\Model\Carrier\Facade */
    private $carrierFacade;

    /** @var \Packetery\Checkout\Model\Log\LogWriter */
    private $logWriter;

    /** @var \Packetery\Checkout\Model\Log\ApiErrorFormatter */
    private $apiErrorFormatter;

    /** @var \Packetery\Checkout\Model\BoxRepository */
    private $boxRepository;

    /** @var \Packetery\Checkout\Model\Dimensions\Converter */
    private $dimensionsConverter;

    /** @var \Packetery\Checkout\Model\OrderCurrencyResolver */
    private $orderCurrencyResolver;

    public function __construct(
        \Packetery\Checkout\Model\Api\SoapApiClient $soapApiClient,
        \Packetery\Checkout\Model\Weight\Calculator $weightCalculator,
        \Packetery\Checkout\Model\PacketFactory $packetFactory,
        \Packetery\Checkout\Model\Packet\SubmitPreconditions $submitPreconditions,
        \Packetery\Checkout\Model\ResourceModel\Packet $packetResource,
        \Packetery\Checkout\Model\ResourceModel\Order $orderResource,
        \Packetery\Checkout\Model\Carrier\Facade $carrierFacade,
        \Packetery\Checkout\Model\Log\LogWriter $logWriter,
        \Packetery\Checkout\Model\Log\ApiErrorFormatter $apiErrorFormatter,
        \Packetery\Checkout\Model\BoxRepository $boxRepository,
        \Packetery\Checkout\Model\Dimensions\Converter $dimensionsConverter,
        \Packetery\Checkout\Model\OrderCurrencyResolver $orderCurrencyResolver
    ) {
        $this->soapApiClient = $soapApiClient;
        $this->weightCalculator = $weightCalculator;
        $this->packetFactory = $packetFactory;
        $this->submitPreconditions = $submitPreconditions;
        $this->packetResource = $packetResource;
        $this->orderResource = $orderResource;
        $this->carrierFacade = $carrierFacade;
        $this->logWriter = $logWriter;
        $this->apiErrorFormatter = $apiErrorFormatter;
        $this->boxRepository = $boxRepository;
        $this->dimensionsConverter = $dimensionsConverter;
        $this->orderCurrencyResolver = $orderCurrencyResolver;
    }

    /**
     * @throws \Packetery\Checkout\Model\Packet\PacketSubmitLocalizedException
     * @throws \Packetery\Checkout\Model\Api\PacketSubmissionException
     */
    public function submitPacket(\Packetery\Checkout\Model\Order $packeteryOrder, \Magento\Sales\Model\Order $magentoOrder): void
    {
        $storeId = (int) $magentoOrder->getStoreId();
        $packeteryConfig = $this->carrierFacade->getPacketeryCarrierConfig($storeId);
        if ($packeteryConfig === null || !$this->submitPreconditions->hasRequiredConfig($packeteryConfig)) {
            throw new \Packetery\Checkout\Model\Packet\PacketSubmitLocalizedException(
                __('API password and Sender must be configured.')
            );
        }

        if ($this->submitPreconditions->isAlreadySubmitted($packeteryOrder->getOrderNumber())) {
            throw new \Packetery\Checkout\Model\Packet\PacketSubmitLocalizedException(
                __('This packet has already been submitted to Packeta.')
            );
        }

        $apiPassword = (string) $packeteryConfig->getApiPassword();
        $sender = (string) $packeteryConfig->getSender();

        $weight = $this->resolveWeight($packeteryOrder, $magentoOrder);

        $value = $packeteryOrder->getValue();
        if ($value === null) {
            $value = (float) $magentoOrder->getGrandTotal();
        }

        $cod = $packeteryOrder->getCod();
        if ($cod === null) {
            $cod = 0.0;
        }

        $currency = (string) $this->orderCurrencyResolver->resolve($packeteryOrder, $magentoOrder);

        $attributes = (new \Packetery\Checkout\Model\Packet\PacketAttributes())
            ->withNumber($packeteryOrder->getOrderNumber())
            ->withName($packeteryOrder->getRecipientFirstname())
            ->withSurname($packeteryOrder->getRecipientLastname())
            ->withCompany($packeteryOrder->getRecipientCompany())
            ->withEmail($packeteryOrder->getRecipientEmail())
            ->withPhone($packeteryOrder->getRecipientPhone())
            ->withAddressId($packeteryOrder->getPointId())
            ->withValue($value)
            ->withCurrency($currency)
            ->withWeight($weight)
            ->withEshop($sender);

        if ($cod > 0.0) {
            $attributes = $attributes->withCod($cod);
        }

        $shippingRateCode = ShippingRateCode::fromString((string) $magentoOrder->getShippingMethod());
        $methodCode = $shippingRateCode->getMethodCode();
        if (Methods::isPickupPointDelivery($methodCode->getMethod())) {
            $carrierPickupPoint = $packeteryOrder->getCarrierPickupPoint();
            if ($carrierPickupPoint !== null) {
                $attributes = $attributes->withCarrierPickupPoint($carrierPickupPoint);
            }
        }

        if (Methods::isAnyAddressDelivery($methodCode->getMethod())) {
            $recipientAddress = $packeteryOrder->getRecipientAddress();
            $attributes = $attributes
                ->withStreet((string) $recipientAddress->getStreet())
                ->withHouseNumber((string) $recipientAddress->getHouseNumber())
                ->withCity((string) $recipientAddress->getCity())
                ->withZip((string) $recipientAddress->getZip());
        }

        $box = $this->resolveBox($packeteryOrder->getBoxId());
        $attributes = $this->withBoxSize($attributes, $box);

        $shippingAddress = $magentoOrder->getShippingAddress();
        $countryId = $shippingAddress !== null ? (string) $shippingAddress->getCountryId() : '';
        if ($packeteryOrder->getAdultContent() === true
            && \Packetery\Checkout\Model\AdultContentResolver::isEligibleForAdultContent($methodCode->getMethod(), $countryId, $packeteryOrder->isCarrier())) {
            $attributes = $attributes->withAdultContent(true);
        }

        try {
            $createResult = $this->soapApiClient->createPacket($apiPassword, $attributes);
        } catch (\Packetery\Checkout\Model\Api\PacketSubmissionException $exception) {
            $this->logWriter->logError(
                \Packetery\Checkout\Model\Log::ACTION_SUBMIT,
                $packeteryOrder->getOrderNumber(),
                $attributes->toArray(),
                $this->apiErrorFormatter->format($exception->getMessage(), $exception->getSoapDetailErrors())
            );
            throw $exception;
        }

        $consignPassword = null;
        if ($packeteryConfig->isShowConsignPassword()) {
            $request = new \Packetery\Checkout\Model\Api\Request\PacketInfoRequest($apiPassword, $createResult->getPacketId());
            $consignPassword = $this->soapApiClient->packetInfo($request)->getConsignPassword();
        }

        $this->savePacket(
            $packeteryOrder->getOrderNumber(),
            $createResult->getPacketId(),
            $weight,
            $value,
            $cod,
            $consignPassword,
            $box
        );

        // Persist the resolved weight so an order that fell back to product/config default
        // no longer stays empty (the record then matches Packeta)
        $packeteryOrder->setWeight($weight);

        $this->markOrderExported($packeteryOrder);

        $this->logWriter->logSuccess(
            \Packetery\Checkout\Model\Log::ACTION_SUBMIT,
            $packeteryOrder->getOrderNumber(),
            __('Submitted packet %1.', $createResult->getPacketId())
        );
    }

    private function resolveBox(?int $boxId): ?\Packetery\Checkout\Model\Box
    {
        if ($boxId === null) {
            return null;
        }

        try {
            return $this->boxRepository->getById($boxId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException) {
            return null;
        }
    }

    /**
     * Adds the box size in whole mm, but only when every dimension is set.
     * A dimension is null only on a box edited directly in the database (the box form requires all dimensions),
     * so the size is skipped instead of letting the strict conversion throw, mirroring the read-only label fallback.
     */
    private function withBoxSize(\Packetery\Checkout\Model\Packet\PacketAttributes $attributes, ?\Packetery\Checkout\Model\Box $box): \Packetery\Checkout\Model\Packet\PacketAttributes
    {
        if ($box === null) {
            return $attributes;
        }

        $depth = $box->getDepth();
        $width = $box->getWidth();
        $height = $box->getHeight();

        if ($depth === null || $width === null || $height === null) {
            return $attributes;
        }

        return $attributes->withSize(
            $this->convertToMm($depth),
            $this->convertToMm($width),
            $this->convertToMm($height)
        );
    }

    /**
     * The Packeta API wants size in whole mm, so the converted cm value is rounded here
     */
    private function convertToMm(float $cm): int
    {
        return (int) round($this->dimensionsConverter->convert($cm, Unit::CM, Unit::MM));
    }

    /**
     * Resolves the submit weight in order: manual > product > per-store configured default
     */
    private function resolveWeight(\Packetery\Checkout\Model\Order $packeteryOrder, \Magento\Sales\Model\Order $magentoOrder): float
    {
        $manualWeight = $packeteryOrder->getWeight();
        if ($manualWeight !== null && $manualWeight > 0.0) {
            return $manualWeight;
        }

        $productWeight = $this->weightCalculator->getOrderWeight($magentoOrder);
        if ($productWeight > 0.0) {
            return $productWeight;
        }

        $config = $this->carrierFacade->getPacketeryCarrierConfig((int) $magentoOrder->getStoreId());
        $defaultWeight = $config !== null ? $config->getDefaultWeight() : null;
        if ($defaultWeight !== null && $defaultWeight > 0.0) {
            return $defaultWeight;
        }

        return $productWeight;
    }

    /**
     * Persists the packet; snapshots the chosen box so the read-only view stays correct even if the box later changes
     */
    private function savePacket(
        string $orderNumber,
        string $packetId,
        float $weight,
        float $value,
        float $cod,
        ?string $consignPassword,
        ?\Packetery\Checkout\Model\Box $box
    ): void {
        /** @var \Packetery\Checkout\Model\Packet $packet */
        $packet = $this->packetFactory->create();
        $packet->setOrderNumber($orderNumber);
        $packet->setPacketNumber($packetId);
        $packet->setWeight($weight);
        $packet->setValue($value);
        $packet->setCod($cod);
        $packet->setConsignPassword($consignPassword);

        if ($box !== null) {
            $packet->setBoxName($box->getName());
            $packet->setBoxWidth($box->getWidth());
            $packet->setBoxHeight($box->getHeight());
            $packet->setBoxDepth($box->getDepth());
        }

        $this->packetResource->save($packet);
    }

    private function markOrderExported(\Packetery\Checkout\Model\Order $packeteryOrder): void
    {
        $packeteryOrder->markExported();
        $this->orderResource->save($packeteryOrder);
    }
}
