<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier\Imp\PacketeryPacketaDynamic;

use Magento\Quote\Model\Quote\Address\RateRequest;

class Carrier extends \Packetery\Checkout\Model\Carrier\AbstractCarrier
{
    /** @var bool  */
    protected $_isFixed = true;

    /** @var \Packetery\Checkout\Model\Carrier\Imp\PacketeryPacketaDynamic\Brain */
    protected $packeteryBrain;

    /** @var \Packetery\Checkout\Model\Carrier\Imp\PacketeryPacketaDynamic\Config */
    protected $packeteryConfig;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Packetery\Checkout\Model\Carrier\Imp\PacketeryPacketaDynamic\Brain $brain
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Packetery\Checkout\Model\Carrier\Imp\PacketeryPacketaDynamic\Brain $brain,
        array $data = []
    ) {
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $brain, $data);
    }

    public function getPacketeryBrain(): \Packetery\Checkout\Model\Carrier\Imp\PacketeryPacketaDynamic\Brain
    {
        return $this->packeteryBrain;
    }

    public function getPacketeryConfig(): \Packetery\Checkout\Model\Carrier\Imp\PacketeryPacketaDynamic\Config
    {
        return $this->packeteryConfig;
    }

    /**
     * @return array
     */
    public function getAllowedMethods(): array
    {
        return [];
    }
}
