<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\ResourceModel\Carrier;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';

    protected $_eventPrefix = 'packetery_checkout_carrier_collection';

    protected $_eventObject = 'carrier_collection';

    /**
     * @return void
     */
    protected function _construct() {
        $this->_init('Packetery\Checkout\Model\Carrier', 'Packetery\Checkout\Model\ResourceModel\Carrier');
    }

    /**
     * @return \Packetery\Checkout\Model\Carrier[]
     */
    public function getItems() {
        return parent::getItems();
    }

    /**
     *  For frontend checkout
     */
    public function resolvableOnly(): void {
        $this->whereDeleted(false);
        $this->supportedOnly();
        $this->wherePricingRuleEnabled(true);
    }

    /**
     * For admin configuration page
     */
    public function configurableOnly(): void {
        $this->whereDeleted(false);
        $this->supportedOnly();
    }

    /**
     * @param bool $value
     */
    private function wherePricingRuleEnabled(bool $value): void {
        $this->leftJoinPricingRules();
        $this->addFilter('pricingRules.enabled', $value);
    }

    public function whereDeleted(bool $value) {
        $this->addFilter('main_table.deleted', $value);
    }

    /**
     * dynamic carriers with attributes not supported by Packetery extension are omitted
     */
    private function supportedOnly(): void {
        $this->addFilter('main_table.disallows_cod', 0); // todo implement payment method filter
        $this->addFilter('main_table.customs_declarations', 0); // todo what does it require? New order edit form fields?
    }

    /**
     * @param string $country
     */
    public function whereCountry(string $country): void {
        $this->addFilter('main_table.country', $country);
    }

    /**
     * @param string $method
     */
    public function forDeliveryMethod(string $method): void {
        if ($method === \Packetery\Checkout\Model\Carrier\Methods::ADDRESS_DELIVERY) {
            $this->addFilter('main_table.is_pickup_points', 0);
            return;
        }

        if ($method === \Packetery\Checkout\Model\Carrier\Methods::PICKUP_POINT_DELIVERY) {
            $this->addFilter('main_table.is_pickup_points', 1);
            return;
        }

        throw new \InvalidArgumentException("Method '{$method}' not supported");
    }

    private function leftJoinPricingRules(): void {
        // cols has to be empty otherwise setDataToAll wont work
        $this->getSelect()->joinLeft(['pricingRules' => $this->getTable('packetery_pricing_rule')], 'main_table.carrier_id = pricingRules.carrier_id', '');
    }
}
