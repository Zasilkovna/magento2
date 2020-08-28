<?php

namespace Packetery\Checkout\Helper\api;


use Packetery\Checkout\Helper\api\Model\ClaimAttributes;
use Packetery\Checkout\Helper\api\Model\PacketAttributes;

interface IApi
{
    public function packetAttributesValid(PacketAttributes $attributes);

    public function packetClaimAttributesValid(ClaimAttributes $attributes);

    public function createPacket(PacketAttributes $attributes);

    public function createPacketClaim(ClaimAttributes $attributes);

    public function createShipment(/*int*/ $packetId, /*string*/ $customBarcode);

    public function packetStatus(/*int*/ $packetId);

    public function packetTracking(/*int*/ $packetId);

    public function packetGetStoredUntil(/*int*/ $packetId);

    public function packetSetStoredUntil(/*int*/ $packetId, \DateTimeInterface $date);

    public function barcodePng(/*string*/ $barcode);

    public function packetLabelPdf(/*int*/ $packetId, /*string*/ $format, /*int*/ $offset);

    public function packetsLabelsPdf(array/*PacketIds*/ $packetIds, /*string*/ $format, /*int*/ $offset);

    public function packetCourierNumber(/*int*/ $packetId);

    public function senderGetReturnRouting(/*string*/ $senderLabel);
}