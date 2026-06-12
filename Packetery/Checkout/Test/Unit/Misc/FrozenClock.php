<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit\Misc;

class FrozenClock implements \Packetery\Checkout\Model\Misc\Clock
{
    public function __construct(private readonly \DateTimeImmutable $now)
    {
    }

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }
}
