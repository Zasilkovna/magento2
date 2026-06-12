<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Misc;

class UtcDateTimeZone extends \DateTimeZone
{
    public function __construct()
    {
        parent::__construct('UTC');
    }
}
