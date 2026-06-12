<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Packet;

class PacketStatusSynchronizer
{
    /** @var \Packetery\Checkout\Model\Api\SoapApiClient */
    private $soapApiClient;

    /** @var \Packetery\Checkout\Model\Carrier\Facade */
    private $carrierFacade;

    /** @var \Packetery\Checkout\Model\PacketRepository */
    private $packetRepository;

    /** @var \Packetery\Checkout\Logger\PacketStatusSyncLogger */
    private $logger;

    /** @var \Packetery\Checkout\Model\Misc\Clock */
    private $clock;

    public function __construct(
        \Packetery\Checkout\Model\Api\SoapApiClient $soapApiClient,
        \Packetery\Checkout\Model\Carrier\Facade $carrierFacade,
        \Packetery\Checkout\Model\PacketRepository $packetRepository,
        \Packetery\Checkout\Logger\PacketStatusSyncLogger $logger,
        \Packetery\Checkout\Model\Misc\Clock $clock
    ) {
        $this->soapApiClient = $soapApiClient;
        $this->carrierFacade = $carrierFacade;
        $this->packetRepository = $packetRepository;
        $this->logger = $logger;
        $this->clock = $clock;
    }

    public function syncStatus(\Packetery\Checkout\Model\Packet $packet, int $storeId): void
    {
        $packeteryConfig = $this->carrierFacade->getPacketeryCarrierConfig($storeId);
        $apiPassword = $packeteryConfig !== null ? $packeteryConfig->getApiPassword() : null;
        if ($apiPassword === null || $apiPassword === '') {
            $this->logger->error(
                'API password is not configured, packet status cannot be synchronized.',
                ['packet_number' => $packet->getPacketNumber(), 'store_id' => $storeId]
            );
            return;
        }

        $result = $this->soapApiClient->packetStatus(
            new \Packetery\Checkout\Model\Api\Request\PacketStatusRequest($apiPassword, $packet->getPacketNumber())
        );

        if ($result->hasFault()) {
            if ($result->hasPacketIdFault()) {
                $packet->setPacketIdFault(true);
                $packet->setStatusSyncedAt($this->clock->now());
                $this->packetRepository->save($packet);
                return;
            }

            $this->logger->error(
                'Packet status could not be synchronized.',
                [
                    'packet_number' => $packet->getPacketNumber(),
                    'fault' => $result->getFault(),
                    'error_message' => $result->getFaultString(),
                ]
            );
            return;
        }

        $codeText = $result->getCodeText();
        if ($codeText !== null && $codeText !== $packet->getPacketStatus()) {
            $packet->setPacketStatus($codeText);
        }

        $packet->setStatusSyncedAt($this->clock->now());
        $this->packetRepository->save($packet);
    }
}
