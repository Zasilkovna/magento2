<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Api\Request;

class BarcodePngRequest
{
    private string $apiPassword;

    private string $barcode;

    public function __construct(string $apiPassword, string $barcode)
    {
        $this->apiPassword = $apiPassword;
        $this->barcode = $barcode;
    }

    public function getApiPassword(): string
    {
        return $this->apiPassword;
    }

    public function getBarcode(): string
    {
        return $this->barcode;
    }
}
