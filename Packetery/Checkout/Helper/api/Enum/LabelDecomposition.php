<?php

namespace Packetery\Checkout\Helper\api\Enum;


class LabelDecomposition
{
    const FULL = 1;
    const QUARTER = 4;

    /** @var array */
    public static $list = [
        self::FULL,
        self::QUARTER
    ];
}