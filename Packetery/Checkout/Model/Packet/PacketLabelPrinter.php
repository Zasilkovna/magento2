<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Packet;

class PacketLabelPrinter
{
    /** @var \Packetery\Checkout\Model\Api\SoapApiClient */
    private $soapApiClient;

    /** @var \Magento\Shipping\Model\CarrierFactory */
    private $carrierFactory;

    /** @var \Packetery\Checkout\Model\ResourceModel\Packet\CollectionFactory */
    private $packetCollectionFactory;

    /** @var \Packetery\Checkout\Model\ResourceModel\Packet */
    private $packetResource;

    public function __construct(
        \Packetery\Checkout\Model\Api\SoapApiClient $soapApiClient,
        \Magento\Shipping\Model\CarrierFactory $carrierFactory,
        \Packetery\Checkout\Model\ResourceModel\Packet\CollectionFactory $packetCollectionFactory,
        \Packetery\Checkout\Model\ResourceModel\Packet $packetResource
    ) {
        $this->soapApiClient = $soapApiClient;
        $this->carrierFactory = $carrierFactory;
        $this->packetCollectionFactory = $packetCollectionFactory;
        $this->packetResource = $packetResource;
    }

    /**
     * @throws PacketLabelLocalizedException
     * @throws \Packetery\Checkout\Model\Api\PacketLabelException
     */
    public function printLabelPdf(
        \Packetery\Checkout\Model\Order $packeteryOrder,
        \Magento\Sales\Model\Order $magentoOrder,
        int $offset
    ): string {
        $storeId = (int) $magentoOrder->getStoreId();

        $packeteryCarrierCode = \Packetery\Checkout\Model\Carrier\Imp\Packetery\Brain::getCarrierCodeStatic();
        $packeteryCarrier = $this->carrierFactory->create($packeteryCarrierCode, $storeId);
        if (!$packeteryCarrier instanceof \Magento\Shipping\Model\Carrier\AbstractCarrier) {
            throw new PacketLabelLocalizedException(__('API password is not configured.'));
        }

        $apiPassword = (string) ($packeteryCarrier->getPacketeryConfig()->getApiPassword() ?? '');
        if ($apiPassword === '') {
            throw new PacketLabelLocalizedException(__('API password is not configured.'));
        }

        $packet = $this->loadLatestPacket($packeteryOrder->getOrderNumber());
        if ($packet === null) {
            throw new PacketLabelLocalizedException(__('No submitted packet was found for this order.'));
        }

        $packetId = $packet->getPacketNumber();
        if ($packetId === '') {
            throw new PacketLabelLocalizedException(__('No submitted packet was found for this order.'));
        }

        $shippingMethod = (string) $magentoOrder->getShippingMethod();
        if (\Packetery\Checkout\Model\Carrier\ShippingRateCode::isPacketery($shippingMethod) === false) {
            throw new PacketLabelLocalizedException(__('Label format is not configured.'));
        }

        $shippingRateCode = \Packetery\Checkout\Model\Carrier\ShippingRateCode::fromString($shippingMethod);
        $carrierCode = $shippingRateCode->getCarrierCode();

        $carrierLabelsCarrierCode = \Packetery\Checkout\Model\Carrier\Imp\PacketeryPacketaDynamic\Brain::getCarrierCodeStatic();
        $isCarrierLabels = $carrierCode === $carrierLabelsCarrierCode;

        $carrier = $this->carrierFactory->create($carrierCode, $storeId);
        if (!$carrier instanceof \Magento\Shipping\Model\Carrier\AbstractCarrier) {
            throw new PacketLabelLocalizedException(__('Label format is not configured.'));
        }

        $format = $carrier->getPacketeryConfig()->getLabelFormat();
        $maxOffset = \Packetery\Checkout\Model\Label\LabelFormats::getMaxOffset($format);
        if ($offset < 0 || $offset > $maxOffset) {
            throw new PacketLabelLocalizedException(__('Invalid label offset.'));
        }

        if ($isCarrierLabels) {
            return $this->printCarrierWithPacketaFallback(
                $apiPassword,
                $packet,
                $packetId,
                $offset,
                $format
            );
        }

        $pdfContents = $this->soapApiClient->packetsLabelsPdf($apiPassword, [$packetId], $format, $offset);
        $this->persistSuccessfulPrint($packet);

        return $pdfContents;
    }

    /**
     * @throws \Packetery\Checkout\Model\Api\PacketLabelException
     */
    private function printCarrierWithPacketaFallback(
        string $apiPassword,
        \Packetery\Checkout\Model\Packet $packet,
        string $packetId,
        int $offset,
        string $labelFormat
    ): string {
        $pairs = $this->resolveCourierPairs($apiPassword, $packet, $packetId);

        if ($pairs !== []) {
            try {
                $pdfContents = $this->soapApiClient->packetsCourierLabelsPdf(
                    $apiPassword,
                    $pairs,
                    $offset,
                    $labelFormat
                );
                $this->persistSuccessfulPrint($packet);

                return $pdfContents;
            } catch (\Packetery\Checkout\Model\Api\PacketLabelException) {
                $pdfContents = $this->soapApiClient->packetsLabelsPdf(
                    $apiPassword,
                    [$packetId],
                    $labelFormat,
                    $offset
                );
                $this->persistSuccessfulPrint($packet);

                return $pdfContents;
            }
        }

        $pdfContents = $this->soapApiClient->packetsLabelsPdf(
            $apiPassword,
            [$packetId],
            $labelFormat,
            $offset
        );
        $this->persistSuccessfulPrint($packet);

        return $pdfContents;
    }

    /**
     * @return array<int, array{packetId: string, courierNumber: string}>
     */
    private function resolveCourierPairs(
        string $apiPassword,
        \Packetery\Checkout\Model\Packet $packet,
        string $packetId
    ): array {
        $existing = $packet->getCourierNumber();
        if ($existing !== null && $existing !== '') {
            return [
                [
                    'packetId' => $packetId,
                    'courierNumber' => $existing,
                ],
            ];
        }

        try {
            $number = $this->soapApiClient->packetCourierNumber($apiPassword, $packetId);
        } catch (\Packetery\Checkout\Model\Api\PacketLabelException) {
            return [];
        }

        $packet->setCourierNumber($number);
        $this->packetResource->save($packet);

        return [
            [
                'packetId' => $packetId,
                'courierNumber' => $number,
            ],
        ];
    }

    private function loadLatestPacket(string $orderNumber): ?\Packetery\Checkout\Model\Packet
    {
        $collection = $this->packetCollectionFactory->create();
        $collection->addFieldToFilter('order_number', $orderNumber);
        $collection->setOrder('id', 'DESC');
        $collection->setPageSize(1);
        $items = $collection->getItems();
        if ($items === []) {
            return null;
        }

        $first = reset($items);
        if (!$first instanceof \Packetery\Checkout\Model\Packet) {
            return null;
        }

        return $first;
    }

    private function persistSuccessfulPrint(\Packetery\Checkout\Model\Packet $packet): void
    {
        $packet->setLabelPrintedAt(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
        $this->packetResource->save($packet);
    }
}
