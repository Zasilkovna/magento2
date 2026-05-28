<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Api\Result;

class BarcodePngResult
{
    private ?string $pngContents = null;

    public function getPngContents(): ?string
    {
        return $this->pngContents;
    }

    public function setPngContents(?string $pngContents): void
    {
        $this->pngContents = $pngContents;
    }
}
