<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;
use Packetery\Checkout\Model\Carrier\Config\AbstractConfig;
use Packetery\Checkout\Model\Carrier\Config\AbstractDynamicConfig;
use Packetery\Checkout\Model\Carrier\Config\AbstractMethodSelect;
use Packetery\Checkout\Model\Misc\ComboPhrase;

/**
 * Use this service to extend custom carriers with new logic that is using dependencies. Good for avoiding constructor hell.
 */
abstract class AbstractBrain
{
    public const PREFIX = 'packetery';
    public const MULTI_SHIPPING_MODULE_NAME = 'multishipping';

    /** @var \Magento\Framework\App\Request\Http */
    protected $httpRequest;

    /** @var \Packetery\Checkout\Model\Pricing\Service */
    protected $pricingService;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    private $scopeConfig;

    /** @var \Packetery\Checkout\Model\Weight\Calculator */
    private $weightCalculator;

    /** @var \Magento\Shipping\Model\Rate\ResultFactory */
    protected $rateResultFactory;

    /** @var \Magento\Framework\App\State */
    protected $appState;

    /**
     * AbstractBrain constructor.
     *
     * @param \Magento\Framework\App\Request\Http $httpRequest
     * @param \Packetery\Checkout\Model\Pricing\Service $pricingService
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Packetery\Checkout\Model\Weight\Calculator $weightCalculator
     * @param \Magento\Framework\App\State $appState
     */
    public function __construct(
        \Magento\Framework\App\Request\Http $httpRequest,
        \Packetery\Checkout\Model\Pricing\Service $pricingService,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Packetery\Checkout\Model\Weight\Calculator $weightCalculator,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Framework\App\State $appState
    ) {
        $this->httpRequest = $httpRequest;
        $this->pricingService = $pricingService;
        $this->scopeConfig = $scopeConfig;
        $this->weightCalculator = $weightCalculator;
        $this->rateResultFactory = $rateResultFactory;
        $this->appState = $appState;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request
     * @return float
     */
    public function getRateRequestWeight(RateRequest $request): float
    {
        /** @var \Magento\Quote\Model\Quote\Item[] $allItems */
        $allItems = $request->getAllItems();
        $allItems = \Packetery\Checkout\Model\Weight\Item::transformItems($allItems);

        return $this->weightCalculator->getItemsWeight($allItems);
    }

    /**
     * @param \Packetery\Checkout\Model\Carrier\AbstractCarrier $carrier
     * @return \Packetery\Checkout\Model\Carrier\Config\AbstractConfig
     */
    abstract public function createConfig(AbstractCarrier $carrier): \Packetery\Checkout\Model\Carrier\Config\AbstractConfig;

    /**
     * @param \Packetery\Checkout\Model\Carrier\Config\AbstractConfig $config
     * @param \Packetery\Checkout\Model\Carrier\AbstractDynamicCarrier|null $dynamicCarrier
     * @return \Packetery\Checkout\Model\Carrier\Config\AbstractConfig
     */
    public function createDynamicConfig(AbstractConfig $config, ?AbstractDynamicCarrier $dynamicCarrier = null): AbstractConfig
    {
        return $config;
    }

    /** Can pricing rule be attached to abstract carrier of this namespace
     * @return bool
     */
    public function isAssignableToPricingRule(): bool
    {
        return true;
    }

    /**
     * @return \Magento\Shipping\Model\Rate\Result
     */
    public function createRateResult(): \Magento\Shipping\Model\Rate\Result
    {
        return $this->rateResultFactory->create();
    }

     /**
     * @param string $carrierCode
     * @param mixed $scope
     * @return mixed
     */
    protected function getConfigData(string $carrierCode, $scope)
    {
        $path = 'carriers/' . $carrierCode;

        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $scope
        );
    }

    /** Returns unique carrier identified in packetery context
     *
     * @return string
     */
    public function getCarrierCode(): string
    {
        return static::getCarrierCodeStatic();
    }

    /** Returns unique carrier identified in packetery context
     *
     * @return string
     */
    public static function getCarrierCodeStatic(): string
    {
        $reflection = new \ReflectionClass(static::class);
        $fileName = $reflection->getFileName();
        $carrierDir = basename(dirname($fileName));

        return lcfirst($carrierDir);
    }

    /**
     * @return \Packetery\Checkout\Model\Carrier\Config\AbstractMethodSelect
     */
    abstract public function getMethodSelect(): AbstractMethodSelect;

    /** Returns data that are used to figure out destination point id
     *
     * @return array
     */
    abstract protected static function getResolvableDestinationData(): array;

    /**
     * @param string $method
     * @param string $countryId
     * @param \Packetery\Checkout\Model\Carrier\AbstractDynamicCarrier|null $dynamicCarrier
     * @return int|null
     */
    public function resolvePointId(string $method, string $countryId, ?AbstractDynamicCarrier $dynamicCarrier = null): ?int
    {
        $data = $this::getResolvableDestinationData();

        return ($data[$method]['countryBranchIds'][$countryId] ?? null);
    }

    /**
     * @param string $carrierName
     * @param \Packetery\Checkout\Model\Carrier\AbstractDynamicCarrier|null $dynamicCarrier
     */
    public function updateDynamicCarrierName(string $carrierName, ?AbstractDynamicCarrier $dynamicCarrier = null): void
    {
    }

    /** Used only by Packeta Dynamic
     *
     * @param int $id
     * @return \Packetery\Checkout\Model\Carrier\AbstractDynamicCarrier|null
     */
    public function getDynamicCarrierById(?int $id): ?AbstractDynamicCarrier
    {
        return null;
    }

    /**
     * @param \Packetery\Checkout\Model\Carrier\AbstractDynamicCarrier $dynamicCarrier
     * @return bool
     */
    public function validateDynamicCarrier(string $method, string $countryId, ?AbstractDynamicCarrier $dynamicCarrier = null): bool
    {
        return true;
    }

    /** What branch ids does carrier implement
     * @return array
     */
    public static function getImplementedBranchIds(): array
    {
        return [];
    }

    /**
     * @param string $method
     * @param string $countryId
     * @param \Packetery\Checkout\Model\Carrier\AbstractDynamicCarrier|null $dynamicCarrier
     * @return bool
     */
    protected function isAvailableForCollection(string $method, string $countryId, ?AbstractDynamicCarrier $dynamicCarrier = null): bool
    {
        if ($method !== Methods::PICKUP_POINT_DELIVERY) {
            if ($this->resolvePointId($method, $countryId, $dynamicCarrier) === null) {
                return false;
            }
        }

        $availableCountries = $this->getAvailableCountries([$method]);

        return in_array($countryId, $availableCountries) && $this->validateDynamicCarrier($method, $countryId, $dynamicCarrier);
    }

    /**
     * @param \Packetery\Checkout\Model\Carrier\AbstractCarrier $carrier
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request
     * @param \Packetery\Checkout\Model\Carrier\AbstractDynamicCarrier|null $dynamicCarrier
     * @return \Magento\Shipping\Model\Rate\Result|null
     */
    public function collectRates(AbstractCarrier $carrier, RateRequest $request, ?AbstractDynamicCarrier $dynamicCarrier = null): ?Result
    {
        $brain = $carrier->getPacketeryBrain();

        $config = $this->createDynamicConfig(
            $carrier->getPacketeryConfig(),
            $dynamicCarrier
        );

        if (!$brain->isCollectionPossible($config)) {
            return null;
        }

        $methods = [];
        foreach ($this->getFinalAllowedMethods($config, $brain->getMethodSelect()) as $selectedMethod) {
            if ($this->isAvailableForCollection($selectedMethod, $request->getDestCountryId(), $dynamicCarrier) === false) {
                continue;
            }

            $methods[$selectedMethod] = $brain->getMethodSelect()->getLabelByValue($selectedMethod);
        }

        $packeteryWeight = $this->getRateRequestWeight($request);
        $request = clone $request;
        $request->setPackageWeight($packeteryWeight);

        $rates = $this->pricingService->collectRates($request, $carrier->getCarrierCode(), $config, $methods, ($dynamicCarrier ? $dynamicCarrier->getDynamicCarrierId() : null));

        if ($rates !== null && $this->appState->getAreaCode() === 'adminhtml' && $dynamicCarrier instanceof AbstractDynamicCarrier) {
            foreach ($rates->getAllRates() as $rate) {
                if ($dynamicCarrier instanceof \Packetery\Checkout\Model\Carrier\Imp\Packetery\VendorCarrier) {
                    $rate->setMethodTitle(new ComboPhrase([VendorGroups::getLabel($dynamicCarrier->getGroup()), ' - ', $rate->getMethodTitle()]));
                } else {
                    $rate->setMethodTitle(new ComboPhrase([$rate->getCarrierTitle(), ' - ', $rate->getMethodTitle()]));
                }

                $rate->setCarrierTitle($carrier->getPacketeryConfig()->getTitle());
            }
        }

        return $rates;
    }

    /**
     * @param \Packetery\Checkout\Model\Carrier\Config\AbstractConfig $config
     * @return bool
     */
    public function isCollectionPossible(AbstractConfig $config): bool
    {
        if ($this->httpRequest->getModuleName() == self::MULTI_SHIPPING_MODULE_NAME) {
            return false;
        }

        if (!$config->isActive()) {
            return false;
        }

        return true;
    }

    /** dynamic carriers visible in configuration
     * @param string $country
     * @param array $methods
     * @return \Packetery\Checkout\Model\Carrier\AbstractDynamicCarrier[]
     */
    public function findConfigurableDynamicCarriers(string $country, array $methods): array
    {
        return [];
    }

    /** dynamic carriers visible in checkout
     *
     * @return \Packetery\Checkout\Model\Carrier\AbstractDynamicCarrier[]
     */
    public function findResolvableDynamicCarriers(): array
    {
        return [];
    }

    /** Static + dynamic countries
     * @param array $methods
     * @return array
     */
    public function getAvailableCountries(array $methods): array
    {
        return [];
    }

    /**
     * @param \Packetery\Checkout\Model\Carrier\Config\AbstractConfig $config
     * @param \Packetery\Checkout\Model\Carrier\Config\AbstractMethodSelect $methodSelect
     * @return array
     */
    public function getFinalAllowedMethods(AbstractConfig $config, AbstractMethodSelect $methodSelect): array
    {
        if ($config instanceof AbstractDynamicConfig) {
            $final = $this->getFinalAllowedMethods($config->getConfig(), $methodSelect);

            return array_intersect($config->getAllowedMethods(), $final);
        }

        $allowedMethods = $config->getAllowedMethods();
        if (empty($allowedMethods)) {
            return $methodSelect->getMethods();
        }

        return $allowedMethods;
    }
}
