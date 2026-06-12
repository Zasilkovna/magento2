<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Packet;

class PacketStatus
{
    public const RECEIVED_DATA = 'received data';
    public const ARRIVED = 'arrived';
    public const PREPARED_FOR_DEPARTURE = 'prepared for departure';
    public const DEPARTED = 'departed';
    public const READY_FOR_PICKUP = 'ready for pickup';
    public const HANDED_TO_CARRIER = 'handed to carrier';
    public const DELIVERED = 'delivered';
    public const POSTED_BACK = 'posted back';
    public const RETURNED = 'returned';
    public const CANCELLED = 'cancelled';
    public const COLLECTED = 'collected';
    public const CUSTOMS = 'customs';
    public const REVERSE_PACKET_ARRIVED = 'reverse packet arrived';
    public const DELIVERY_ATTEMPT = 'delivery attempt';
    public const REJECTED_BY_RECIPIENT = 'rejected by recipient';
    public const RETURN_FROM_HD_NO_BRANCH_NEARBY = 'return from hd no branch nearby';
    public const STORAGE_TIME_EXPIRED = 'storage time expired';
    public const PACKET_CANCELLED_BUT_CONSIGNED = 'packet cancelled but consigned';
    public const RETURN_OVERLIMIT = 'return overlimit';
    public const ZBOX_DELIVERY_ATTEMPT = 'zbox delivery attempt';
    public const ZBOX_LAST_DELIVERY_ATTEMPT = 'zbox last delivery attempt';
    public const CARRIER_FIRST_DELIVERY_ATTEMPT = 'carrier first delivery attempt';
    public const PACKET_UNDER_INVESTIGATION = 'packet under investigation';
    public const PACKET_INVESTIGATION_RESOLVED = 'packet investigation resolved';
    public const FAVOURITE_POINT_REDIRECT = 'favourite point redirect';
    public const NO_FAVOURITE_POINT_AVAILABLE_REDIRECT = 'no favourite point available redirect';
    public const NO_FAVOURITE_POINT_SET_REDIRECT = 'no favourite point set redirect';
    public const COURIER_TRACKING_CODE_ADDED = 'courier tracking code added';
    public const UNKNOWN = 'unknown';

    /**
     * Synthetic grid marker for packets the Packeta API reports as non-existent (PacketIdFault).
     * It is never stored as a packet status codeText to avoid collision with future API codes.
     */
    public const DOES_NOT_EXIST = 'packet-does-not-exist';

    /** @var string */
    private $codeText;

    /** @var \Magento\Framework\Phrase */
    private $translatedName;

    /** @var bool */
    private $final;

    public function __construct(string $codeText, \Magento\Framework\Phrase $translatedName, bool $final)
    {
        $this->codeText = $codeText;
        $this->translatedName = $translatedName;
        $this->final = $final;
    }

    public function getCodeText(): string
    {
        return $this->codeText;
    }

    public function getTranslatedName(): \Magento\Framework\Phrase
    {
        return $this->translatedName;
    }

    public function isFinal(): bool
    {
        return $this->final;
    }
}
