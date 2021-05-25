<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Packetery\Checkout\Model\Carrier\Config\AbstractConfig;

/**
 * Use this service to extend custom carriers with new logic that is using dependencies. Good for avoiding constructor hell.
 */
abstract class AbstractBrain
{
    public const PREFIX = 'packetery';
    public const MULTI_SHIPPING_MODULE_NAME = 'multishipping';

    /** @var \Magento\Framework\App\Request\Http  */
    protected $httpRequest;

    /** @var \Packetery\Checkout\Model\Pricing\Service  */
    protected $pricingService;

    /**
     * CarrierBrain constructor.
     *
     * @param \Magento\Framework\App\Request\Http $httpRequest
     * @param \Packetery\Checkout\Model\Pricing\Service $pricingService
     */
    public function __construct(
        \Magento\Framework\App\Request\Http $httpRequest,
        \Packetery\Checkout\Model\Pricing\Service $pricingService
    )
    {
        $this->httpRequest = $httpRequest;
        $this->pricingService = $pricingService;
    }

    /**
     * @param \Packetery\Checkout\Model\Carrier\AbstractCarrier $carrier
     * @return \Packetery\Checkout\Model\Carrier\Config\AbstractConfig
     */
    abstract public function createConfig(AbstractCarrier $carrier): \Packetery\Checkout\Model\Carrier\Config\AbstractConfig;

    /** Returns unique carrier identified in packetery context
     * @return string
     */
    public function getCarrierCode(): string {
        $reflection = new \ReflectionClass(static::class);
        $fileName = $reflection->getFileName();
        $carrierDir = basename(dirname($fileName));
        return lcfirst($carrierDir);
    }

    /**
     * @return \Packetery\Checkout\Model\Carrier\Config\AbstractMethodSelect
     */
    abstract public function getMethodSelect(): \Packetery\Checkout\Model\Carrier\Config\AbstractMethodSelect;

    /**
     * @return \Packetery\Checkout\Model\Carrier\Config\AbstractCountrySelect
     */
    abstract public function getCountrySelect(): \Packetery\Checkout\Model\Carrier\Config\AbstractCountrySelect;

    /** Returns data that are used to figure out destination point id
     * @return array
     */
    abstract protected function getResolvableDestinationData(): array;

    /**
     * @param string $countryId
     * @return int|null
     */
    public function resolvePointId(string $method, string $countryId): ?int
    {
        $data = $this->getResolvableDestinationData();
        return ($data[$method]['countryBranchIds'][$countryId] ?? null);
    }

    /**
     * @param \Packetery\Checkout\Model\Carrier\AbstractCarrier $carrier
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request
     * @return false|\Magento\Shipping\Model\Rate\Result
     */
    public function collectRates(AbstractCarrier $carrier, RateRequest $request)
    {
        if (!$this->isCollectionPossible($carrier->getPacketeryConfig(), $request->getDestCountryId())) {
            return false;
        }

        $result = $this->pricingService->collectRates($request, $carrier->getCarrierCode(), $carrier->getPacketeryConfig(), $carrier->getPacketeryBrain());
        if (!$result instanceof \Magento\Shipping\Model\Rate\Result) {
            return false;
        }

        return $result;
    }

    /**
     * @param \Packetery\Checkout\Model\Carrier\Config\AbstractConfig $config
     * @param string $countryId
     * @return bool
     */
    public function isCollectionPossible(AbstractConfig $config, string $countryId): bool
    {
        if ($this->httpRequest->getModuleName() == self::MULTI_SHIPPING_MODULE_NAME) {
            return false;
        }

        if (!$config->isActive()) {
            return false;
        }

        if (!$config->hasSpecificCountryAllowed($countryId)) {
            return false;
        }

        return true;
    }
}
