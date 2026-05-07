<?php

declare(strict_types=1);

namespace Packetery\Checkout\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class BulkPacketSubmit extends Base
{
    /** @var string */
    protected $fileName = '/var/log/packetery_bulk_packet_submit.log';

    /** @var int */
    protected $loggerType = Logger::INFO;
}

