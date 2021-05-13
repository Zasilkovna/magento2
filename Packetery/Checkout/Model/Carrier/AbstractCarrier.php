<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier;

use Magento\Shipping\Model\Carrier\CarrierInterface;
use Packetery\Checkout\Model\Carrier\Config\AbstractConfig;
use Packetery\Checkout\Model\Carrier\Config\AllowedMethods;

abstract class AbstractCarrier extends \Magento\Shipping\Model\Carrier\AbstractCarrier implements CarrierInterface
{
    const MULTI_SHIPPING_MODULE_NAME = 'multishipping';

    const PREFIX = 'packetery';

    /** @var AbstractConfig */
    protected $carrierConfig;

    /** @var \Packetery\Checkout\Model\Carrier\Brain */
    protected $brain;

    /**
     * AbstractCarrier constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Packetery\Checkout\Model\Carrier\Brain $brain
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Packetery\Checkout\Model\Carrier\Brain $brain,
        array $data = []
    ) {
        $this->_code = self::PREFIX . $this->getPacketeryCode();
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
        $this->brain = $brain;
        $this->carrierConfig = $this->createConfig();
    }

    /** fixes DI cycling and avoids real class creation
     *
     * @return \Packetery\Checkout\Model\Carrier\Config\AbstractConfig
     */
    abstract protected function createConfig(): AbstractConfig;

    /**
     * @return \Packetery\Checkout\Model\Carrier\Config\AbstractConfig
     */
    public function getConfig(): AbstractConfig
    {
        return $this->carrierConfig;
    }

    /** Returns unique carrier identified in packetery context
     * @return string
     */
    abstract protected function getPacketeryCode(): string;

    /**
     * @return \Packetery\Checkout\Model\Carrier\Config\AbstractMethodSelect
     */
    abstract public function getMethodSelect(): \Packetery\Checkout\Model\Carrier\Config\AbstractMethodSelect;

    /**
     * @return \Magento\Directory\Model\Config\Source\Country
     */
    abstract public function getCountrySelect(): \Packetery\Checkout\Model\Carrier\Config\AbstractCountrySelect;

    /** Returns data that are used to figure out destination point id
     * @return int[]
     */
    protected function getResolvableDestinationData(): array
    {
        return [
            AllowedMethods::ADDRESS_DELIVERY => [
                'countryBranchIds' => [
                    'CZ' => 106,
                    'SK' => 131,
                    'HU' => 4159,
                    'RO' => 4161,
                    'PL' => 4162,
                ]
            ]
        ];
    }

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
     * getAllowedMethods
     *
     * @param array
     */
    public function getAllowedMethods(): array
    {
        $result = [];
        $config = $this->getConfig();
        $selectedMethods = $config->getFinalAllowedMethods();
        $select = $this->getMethodSelect();

        foreach ($selectedMethods->toArray() as $selectedMethod) {
            $result[$selectedMethod] = $select->getLabelByValue($selectedMethod);
        }

        return $result;
    }
}
