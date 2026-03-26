<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Packet;

use Packetery\Checkout\Model\Api\Request\CancelPacketRequest;
use Packetery\Checkout\Model\Carrier\ShippingRateCode;
use Packetery\Checkout\Model\Packet\PacketCancelLocalizedException;

class PacketCanceler
{
    public function __construct(
        private readonly \Packetery\Checkout\Model\Api\SoapApiClient $soapApiClient,
        private readonly \Magento\Shipping\Model\CarrierFactory $carrierFactory,
        private readonly \Packetery\Checkout\Model\ResourceModel\Order $orderResource,
        private readonly \Packetery\Checkout\Model\ResourceModel\Packet\CollectionFactory $packetCollectionFactory,
        private readonly \Packetery\Checkout\Model\ResourceModel\Packet $packetResource
    ) {
    }

    /**
     * @throws \Packetery\Checkout\Model\Packet\PacketCancelLocalizedException
     */
    public function cancelPacket(
        \Packetery\Checkout\Model\Order $packeteryOrder,
        \Magento\Sales\Model\Order $magentoOrder,
        string $packetNumber
    ): void {
        $packetNumber = trim($packetNumber);
        $trackingNumber = $packetNumber !== '' ? ('Z' . $packetNumber) : '';
        if ($trackingNumber === '') {
            throw new PacketCancelLocalizedException(__('The packet %1 could not be canceled.', $trackingNumber));
        }

        $shippingMethodString = (string) $magentoOrder->getShippingMethod();
        if (ShippingRateCode::isPacketery($shippingMethodString) === false) {
            throw new PacketCancelLocalizedException(__('The packet %1 could not be canceled.', $trackingNumber));
        }

        $storeId = (int) $magentoOrder->getStoreId();
        $packeteryCarrierCode = \Packetery\Checkout\Model\Carrier\Imp\Packetery\Brain::getCarrierCodeStatic();
        $packeteryCarrier = $this->carrierFactory->create($packeteryCarrierCode, $storeId);
        if (!$packeteryCarrier instanceof \Magento\Shipping\Model\Carrier\AbstractCarrier) {
            throw new PacketCancelLocalizedException(__('Packeta carrier is not configured.'));
        }

        $apiPassword = (string) ($packeteryCarrier->getPacketeryConfig()->getApiPassword() ?? '');
        if ($apiPassword === '') {
            throw new PacketCancelLocalizedException(__('API password is not configured.'));
        }

        $request = new CancelPacketRequest($apiPassword, $packetNumber);
        $result = $this->soapApiClient->cancelPacket($request);
        if ($result->hasFault() && !$result->hasCancelNotAllowedFault()) {
            throw new PacketCancelLocalizedException(__('The packet %1 could not be canceled.', $trackingNumber));
        }

        $this->deletePacketRows($packeteryOrder->getOrderNumber(), $packetNumber);
        $packeteryOrder->setData('exported', 0);
        $this->orderResource->save($packeteryOrder);
    }

    private function deletePacketRows(string $orderNumber, string $packetNumber): void
    {
        $collection = $this->packetCollectionFactory->create();
        $collection->addFieldToFilter('order_number', $orderNumber);
        $collection->addFieldToFilter('packet_number', $packetNumber);

        /** @var \Packetery\Checkout\Model\Packet[] $packets */
        $packets = $collection->getItems();
        foreach ($packets as $packet) {
            $this->packetResource->delete($packet);
        }
    }
}
