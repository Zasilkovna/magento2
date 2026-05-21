<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Api\Result;

class CreateShipmentResult
{
    private ?string $barcode = null;

    private ?string $barcodeText = null;

    public function getBarcode(): ?string
    {
        return $this->barcode;
    }

    public function setBarcode(?string $barcode): void
    {
        $this->barcode = $barcode;
    }

    public function getBarcodeText(): ?string
    {
        return $this->barcodeText;
    }

    public function setBarcodeText(?string $barcodeText): void
    {
        $this->barcodeText = $barcodeText;
    }
}
