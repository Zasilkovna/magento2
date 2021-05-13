<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier\Imp\Packetery;

class Brain extends \Packetery\Checkout\Model\Carrier\Brain
{
    /** @var \Packetery\Checkout\Model\Config\Source\MethodSelect */
    private $methodSelect;

    /** @var \Packetery\Checkout\Model\Carrier\Imp\Packetery\CountrySelect */
    private $countrySelect;

    /**
     * Brain constructor.
     *
     * @param \Magento\Framework\App\Request\Http $httpRequest
     * @param \Packetery\Checkout\Model\Pricing\Service $pricingService
     * @param \Packetery\Checkout\Model\Config\Source\MethodSelect $methodSelect
     * @param \Packetery\Checkout\Model\Carrier\Imp\Packetery\CountrySelect $countrySelect
     */
    public function __construct(
        \Magento\Framework\App\Request\Http $httpRequest,
        \Packetery\Checkout\Model\Pricing\Service $pricingService,
        \Packetery\Checkout\Model\Config\Source\MethodSelect $methodSelect,
        \Packetery\Checkout\Model\Carrier\Imp\Packetery\CountrySelect $countrySelect
    )
    {
        parent::__construct($httpRequest, $pricingService);
        $this->methodSelect = $methodSelect;
        $this->countrySelect = $countrySelect;
    }

    /**
     * @return \Packetery\Checkout\Model\Config\Source\MethodSelect
     */
    public function getMethodSelect(): \Packetery\Checkout\Model\Config\Source\MethodSelect
    {
        return $this->methodSelect;
    }

    /**
     * @return \Packetery\Checkout\Model\Carrier\Imp\Packetery\CountrySelect
     */
    public function getCountrySelect(): \Packetery\Checkout\Model\Carrier\Imp\Packetery\CountrySelect {
        return $this->countrySelect;
    }
}
