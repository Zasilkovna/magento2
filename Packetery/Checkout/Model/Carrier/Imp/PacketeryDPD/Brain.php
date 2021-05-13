<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier\Imp\PacketeryDPD;

class Brain extends \Packetery\Checkout\Model\Carrier\Brain
{
    /** @var \Packetery\Checkout\Model\Carrier\Imp\PacketeryDPD\CountrySelect */
    private $countrySelect;

    /**
     * Brain constructor.
     *
     * @param \Packetery\Checkout\Model\Carrier\Imp\PacketeryDPD\CountrySelect $countrySelect
     */
    public function __construct(
        \Magento\Framework\App\Request\Http $httpRequest,
        \Packetery\Checkout\Model\Pricing\Service $pricingService,
        CountrySelect $countrySelect
    )
    {
        parent::__construct($httpRequest, $pricingService);
        $this->countrySelect = $countrySelect;
    }

    /**
     * @return \Packetery\Checkout\Model\Carrier\Imp\PacketeryDPD\CountrySelect
     */
    public function getCountrySelect(): CountrySelect
    {
        return $this->countrySelect;
    }
}
