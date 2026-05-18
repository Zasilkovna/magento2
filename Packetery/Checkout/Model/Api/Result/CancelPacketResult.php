<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Api\Result;

class CancelPacketResult
{
    private ?string $fault = null;
    private ?string $faultString = null;

    public function hasCancelNotAllowedFault(): bool
    {
        return ($this->fault === 'CancelNotAllowedFault');
    }

    public function setFault(string $fault): void
    {
        $this->fault = $fault;
    }

    public function getFault(): ?string
    {
        return $this->fault;
    }

    public function hasFault(): bool
    {
        return ($this->fault !== null && $this->fault !== '');
    }

    public function setFaultString(string $faultString): void
    {
        $this->faultString = $faultString;
    }

    public function getFaultString(): ?string
    {
        return $this->faultString;
    }
}
