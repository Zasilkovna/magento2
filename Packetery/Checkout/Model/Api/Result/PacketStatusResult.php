<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Api\Result;

class PacketStatusResult
{
    public const FAULT_PACKET_ID = 'PacketIdFault';

    private ?string $codeText = null;

    private string $fault = '';

    private string $faultString = '';

    public function getCodeText(): ?string
    {
        return $this->codeText;
    }

    public function setCodeText(?string $codeText): void
    {
        $this->codeText = $codeText;
    }

    public function getFault(): string
    {
        return $this->fault;
    }

    public function setFault(string $fault): void
    {
        $this->fault = $fault;
    }

    public function getFaultString(): string
    {
        return $this->faultString;
    }

    public function setFaultString(string $faultString): void
    {
        $this->faultString = $faultString;
    }

    public function hasFault(): bool
    {
        return $this->fault !== '';
    }

    public function hasPacketIdFault(): bool
    {
        return $this->fault === self::FAULT_PACKET_ID;
    }
}
