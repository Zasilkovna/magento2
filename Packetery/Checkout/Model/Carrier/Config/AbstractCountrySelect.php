<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Carrier\Config;

/**
 * Represents all possible country options for given carrier
 */
abstract class AbstractCountrySelect extends \Magento\Directory\Model\Config\Source\Country
{
    public function __construct(\Magento\Directory\Model\ResourceModel\Country\Collection $countryCollection)
    {
        parent::__construct($countryCollection);
        $countryIds = $this->getCountryIds();
        if ($countryIds !== null) {
            $this->_countryCollection->addFieldToFilter('country_id', ['in' => $countryIds]);
        }
    }

    /**
     * @return array
     */
    abstract protected function getCountryIds(): ?array;

    /**
     * @param string $value
     * @return \Magento\Framework\Phrase|null
     */
    public function getLabelByValue(string $value): ?\Magento\Framework\Phrase
    {
        $options = $this->toOptionArray();

        foreach ($options as $option) {
            if ($option['value'] === $value) {
                return __((string)$option['label']);
            }
        }

        return null;
    }
}
