<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier\Config;

/**
 * Merged configs of dynamic carrier and fixed Magento carrier
 */
abstract class AbstractDynamicConfig extends AbstractConfig
{
    abstract public function getConfig(): AbstractConfig;
}
