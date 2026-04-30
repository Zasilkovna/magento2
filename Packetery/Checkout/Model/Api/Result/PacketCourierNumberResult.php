<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Api\Result;

class PacketCourierNumberResult
{
    private ?string $courierNumber = null;

    private string $fault = '';

    private string $faultString = '';

    public function getCourierNumber(): ?string
    {
        return $this->courierNumber;
    }

    public function setCourierNumber(?string $courierNumber): void
    {
        $this->courierNumber = $courierNumber;
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

