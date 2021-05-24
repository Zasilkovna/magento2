<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier\Imp\PacketeryDPD;

class Brain extends \Packetery\Checkout\Model\Carrier\Brain
{
    /** @var \Packetery\Checkout\Model\Carrier\Imp\PacketeryDPD\CountrySelect */
    private $countrySelect;

    /** @var \Packetery\Checkout\Model\Carrier\Imp\PacketeryDPD\MethodSelect */
    private $methodSelect;

    /**
     * Brain constructor.
     *
     * @param \Magento\Framework\App\Request\Http $httpRequest
     * @param \Packetery\Checkout\Model\Pricing\Service $pricingService
     * @param \Packetery\Checkout\Model\Carrier\Imp\PacketeryDPD\CountrySelect $countrySelect
     * @param \Packetery\Checkout\Model\Carrier\Imp\PacketeryDPD\MethodSelect $methodSelect
     */
    public function __construct(
        \Magento\Framework\App\Request\Http $httpRequest,
        \Packetery\Checkout\Model\Pricing\Service $pricingService,
        CountrySelect $countrySelect,
        MethodSelect $methodSelect
    )
    {
        parent::__construct($httpRequest, $pricingService);
        $this->countrySelect = $countrySelect;
        $this->methodSelect = $methodSelect;
    }

    /**
     * @return \Packetery\Checkout\Model\Carrier\Imp\PacketeryDPD\CountrySelect
     */
    public function getCountrySelect(): CountrySelect
    {
        return $this->countrySelect;
    }

    public function getMethodSelect(): MethodSelect {
        return $this->methodSelect;
    }
}
