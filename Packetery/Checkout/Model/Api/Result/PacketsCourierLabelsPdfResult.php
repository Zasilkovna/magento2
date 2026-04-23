<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Api\Result;

class PacketsCourierLabelsPdfResult
{
    private ?string $pdfContents = null;

    private string $fault = '';

    private string $faultString = '';

    /** @var string[] */
    private array $invalidPacketIds = [];

    /** @var string[] */
    private array $invalidCourierNumbers = [];

    public function getPdfContents(): ?string
    {
        return $this->pdfContents;
    }

    public function setPdfContents(?string $pdfContents): void
    {
        $this->pdfContents = $pdfContents;
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

    /**
     * @return string[]
     */
    public function getInvalidPacketIds(): array
    {
        return $this->invalidPacketIds;
    }

    /**
     * @param string[] $invalidPacketIds
     */
    public function setInvalidPacketIds(array $invalidPacketIds): void
    {
        $this->invalidPacketIds = $invalidPacketIds;
    }

    /**
     * @return string[]
     */
    public function getInvalidCourierNumbers(): array
    {
        return $this->invalidCourierNumbers;
    }

    /**
     * @param string[] $invalidCourierNumbers
     */
    public function setInvalidCourierNumbers(array $invalidCourierNumbers): void
    {
        $this->invalidCourierNumbers = $invalidCourierNumbers;
    }
}

