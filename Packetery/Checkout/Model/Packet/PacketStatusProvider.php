<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Packet;

class PacketStatusProvider
{
    /** @var array<string, \Packetery\Checkout\Model\Packet\PacketStatus>|null Keyed by codeText. */
    private $statuses = null;

    /**
     * @return array<string, \Packetery\Checkout\Model\Packet\PacketStatus> Keyed by codeText.
     */
    public function getAll(): array
    {
        if ($this->statuses !== null) {
            return $this->statuses;
        }

        $statuses = [
            new \Packetery\Checkout\Model\Packet\PacketStatus(
                \Packetery\Checkout\Model\Packet\PacketStatus::RECEIVED_DATA,
                __('Awaiting consignment'),
                false
            ),
            new \Packetery\Checkout\Model\Packet\PacketStatus(
                \Packetery\Checkout\Model\Packet\PacketStatus::ARRIVED,
                __('Accepted at depot'),
                false
            ),
            new \Packetery\Checkout\Model\Packet\PacketStatus(
                \Packetery\Checkout\Model\Packet\PacketStatus::PREPARED_FOR_DEPARTURE,
                __('On the way'),
                false
            ),
            new \Packetery\Checkout\Model\Packet\PacketStatus(
                \Packetery\Checkout\Model\Packet\PacketStatus::DEPARTED,
                __('Departed from depot'),
                false
            ),
            new \Packetery\Checkout\Model\Packet\PacketStatus(
                \Packetery\Checkout\Model\Packet\PacketStatus::READY_FOR_PICKUP,
                __('Ready for pick-up'),
                false
            ),
            new \Packetery\Checkout\Model\Packet\PacketStatus(
                \Packetery\Checkout\Model\Packet\PacketStatus::HANDED_TO_CARRIER,
                __('Handed over to carrier company'),
                false
            ),
            new \Packetery\Checkout\Model\Packet\PacketStatus(
                \Packetery\Checkout\Model\Packet\PacketStatus::DELIVERED,
                __('Delivered'),
                true
            ),
            new \Packetery\Checkout\Model\Packet\PacketStatus(
                \Packetery\Checkout\Model\Packet\PacketStatus::POSTED_BACK,
                __('Returning (on the way back)'),
                false
            ),
            new \Packetery\Checkout\Model\Packet\PacketStatus(
                \Packetery\Checkout\Model\Packet\PacketStatus::RETURNED,
                __('Returned to sender'),
                true
            ),
            new \Packetery\Checkout\Model\Packet\PacketStatus(
                \Packetery\Checkout\Model\Packet\PacketStatus::CANCELLED,
                __('Cancelled'),
                true
            ),
            new \Packetery\Checkout\Model\Packet\PacketStatus(
                \Packetery\Checkout\Model\Packet\PacketStatus::COLLECTED,
                __('Parcel has been collected'),
                false
            ),
            new \Packetery\Checkout\Model\Packet\PacketStatus(
                \Packetery\Checkout\Model\Packet\PacketStatus::CUSTOMS,
                __('Customs declaration process'),
                false
            ),
            new \Packetery\Checkout\Model\Packet\PacketStatus(
                \Packetery\Checkout\Model\Packet\PacketStatus::REVERSE_PACKET_ARRIVED,
                __('Reverse parcel has been accepted at our pick up point'),
                false
            ),
            new \Packetery\Checkout\Model\Packet\PacketStatus(
                \Packetery\Checkout\Model\Packet\PacketStatus::DELIVERY_ATTEMPT,
                __('Unsuccessful delivery attempt of parcel'),
                false
            ),
            new \Packetery\Checkout\Model\Packet\PacketStatus(
                \Packetery\Checkout\Model\Packet\PacketStatus::REJECTED_BY_RECIPIENT,
                __('Rejected by recipient response'),
                false
            ),
            new \Packetery\Checkout\Model\Packet\PacketStatus(
                \Packetery\Checkout\Model\Packet\PacketStatus::RETURN_FROM_HD_NO_BRANCH_NEARBY,
                __('Returning — no nearby pick-up point'),
                false
            ),
            new \Packetery\Checkout\Model\Packet\PacketStatus(
                \Packetery\Checkout\Model\Packet\PacketStatus::STORAGE_TIME_EXPIRED,
                __('Storage time expired'),
                false
            ),
            new \Packetery\Checkout\Model\Packet\PacketStatus(
                \Packetery\Checkout\Model\Packet\PacketStatus::PACKET_CANCELLED_BUT_CONSIGNED,
                __('Cancelled but consigned (will be returned)'),
                false
            ),
            new \Packetery\Checkout\Model\Packet\PacketStatus(
                \Packetery\Checkout\Model\Packet\PacketStatus::RETURN_OVERLIMIT,
                __('Returning — over limit'),
                false
            ),
            new \Packetery\Checkout\Model\Packet\PacketStatus(
                \Packetery\Checkout\Model\Packet\PacketStatus::ZBOX_DELIVERY_ATTEMPT,
                __('Z-BOX delivery attempt'),
                false
            ),
            new \Packetery\Checkout\Model\Packet\PacketStatus(
                \Packetery\Checkout\Model\Packet\PacketStatus::ZBOX_LAST_DELIVERY_ATTEMPT,
                __('Z-BOX last delivery attempt'),
                false
            ),
            new \Packetery\Checkout\Model\Packet\PacketStatus(
                \Packetery\Checkout\Model\Packet\PacketStatus::CARRIER_FIRST_DELIVERY_ATTEMPT,
                __('Carrier first delivery attempt'),
                false
            ),
            new \Packetery\Checkout\Model\Packet\PacketStatus(
                \Packetery\Checkout\Model\Packet\PacketStatus::PACKET_UNDER_INVESTIGATION,
                __('Under investigation'),
                false
            ),
            new \Packetery\Checkout\Model\Packet\PacketStatus(
                \Packetery\Checkout\Model\Packet\PacketStatus::PACKET_INVESTIGATION_RESOLVED,
                __('Investigation resolved'),
                false
            ),
            new \Packetery\Checkout\Model\Packet\PacketStatus(
                \Packetery\Checkout\Model\Packet\PacketStatus::FAVOURITE_POINT_REDIRECT,
                __('Redirected to favourite point'),
                false
            ),
            new \Packetery\Checkout\Model\Packet\PacketStatus(
                \Packetery\Checkout\Model\Packet\PacketStatus::NO_FAVOURITE_POINT_AVAILABLE_REDIRECT,
                __('Redirected — favourite points full'),
                false
            ),
            new \Packetery\Checkout\Model\Packet\PacketStatus(
                \Packetery\Checkout\Model\Packet\PacketStatus::NO_FAVOURITE_POINT_SET_REDIRECT,
                __('Redirected — no favourite point set'),
                false
            ),
            new \Packetery\Checkout\Model\Packet\PacketStatus(
                \Packetery\Checkout\Model\Packet\PacketStatus::COURIER_TRACKING_CODE_ADDED,
                __('Courier tracking code added'),
                false
            ),
            new \Packetery\Checkout\Model\Packet\PacketStatus(
                \Packetery\Checkout\Model\Packet\PacketStatus::UNKNOWN,
                __('Unknown parcel status'),
                true
            ),
        ];

        $indexed = [];
        foreach ($statuses as $status) {
            $indexed[$status->getCodeText()] = $status;
        }
        $this->statuses = $indexed;

        return $this->statuses;
    }

    public function getByCodeText(string $codeText): ?\Packetery\Checkout\Model\Packet\PacketStatus
    {
        return $this->getAll()[$codeText] ?? null;
    }

    /**
     * Unlisted codes are treated as non-final so a packet is never accidentally dropped from tracking.
     */
    public function isFinal(string $codeText): bool
    {
        $status = $this->getByCodeText($codeText);
        if ($status === null) {
            return false;
        }

        return $status->isFinal();
    }

    public function getTranslatedName(string $codeText): \Magento\Framework\Phrase
    {
        $status = $this->getByCodeText($codeText);
        if ($status === null) {
            return __($codeText);
        }

        return $status->getTranslatedName();
    }

    /**
     * @return string[]
     */
    public function getFinalCodeTexts(): array
    {
        $codeTexts = [];
        foreach ($this->getAll() as $status) {
            if ($status->isFinal()) {
                $codeTexts[] = $status->getCodeText();
            }
        }

        return $codeTexts;
    }
}
