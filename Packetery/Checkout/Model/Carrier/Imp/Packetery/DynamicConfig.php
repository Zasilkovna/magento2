<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier\Imp\Packetery;

use Packetery\Checkout\Model\Carrier\AbstractDynamicCarrier;
use Packetery\Checkout\Model\Carrier\Config\AbstractConfig;

class DynamicConfig extends \Packetery\Checkout\Model\Carrier\Config\AbstractDynamicConfig
{
    /** @var AbstractDynamicCarrier */
    private $carrier;

    /** @var Config */
    private $config;

    public function __construct(Config $config, AbstractDynamicCarrier $carrier) {
        parent::__construct($config->toArray());
        $this->carrier = $carrier;
        $this->config = $config;
    }

    /**
     * @return \Magento\Framework\Phrase|string
     */
    public function getTitle() {
        return $this->carrier->getFinalCarrierName();
    }

    /**
     * @return string[]
     */
    public function getAllowedMethods(): array {
        return $this->carrier->getMethods();
    }

    public function getConfig(): AbstractConfig {
        return $this->config;
    }
}
