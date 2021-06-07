<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier\Imp\PacketeryPacketaDynamic;

/**
 * PacketaDynamic aggregates feed carriers. Each pricing request requires single carrier.
 */
class DynamicConfig extends \Packetery\Checkout\Model\Carrier\Config\AbstractConfig
{
    /** @var \Packetery\Checkout\Model\Carrier */
    private $carrier;

    /** @var array */
    private $finalMethods;

    /**
     * @param array $data
     */
    public function __construct(array $data, \Packetery\Checkout\Model\Carrier $carrier, array $finalMethods)
    {
        parent::__construct($data);
        $this->carrier = $carrier;
        $this->finalMethods = $finalMethods;
    }

    /**
     * @return bool
     */
    public function isActive(): bool {
        return parent::isActive() && !$this->carrier->getDeleted();
    }

    /**
     * @return \Magento\Framework\Phrase|string
     */
    public function getTitle() {
        return $this->carrier->getFinalCarrierName();
    }

    /**
     * @return array
     */
    public function getAllowedMethods(): array {
        $dynamicCarrierMethods = [$this->carrier->getMethod()];
        return array_diff($this->finalMethods, $dynamicCarrierMethods);
    }
}
