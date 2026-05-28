<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Api\Result;

class PacketInfoResult
{
    private ?string $consignPassword = null;

    private string $fault = '';

    private string $faultString = '';

    public function getConsignPassword(): ?string
    {
        return $this->consignPassword;
    }

    public function setConsignPassword(?string $consignPassword): void
    {
        $this->consignPassword = $consignPassword;
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
}
