<?php

declare(strict_types=1);

namespace Packetery\Checkout\Logger\Handler;

class PacketStatusSync extends \Magento\Framework\Logger\Handler\Base
{
    /** @var string */
    protected $fileName = '/var/log/packetery_packet_status_sync.log';

    /** @var int */
    protected $loggerType = \Monolog\Logger::INFO;
}
