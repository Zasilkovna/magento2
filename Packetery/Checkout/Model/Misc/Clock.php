<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Misc;

interface Clock
{
    public function now(): \DateTimeImmutable;
}
