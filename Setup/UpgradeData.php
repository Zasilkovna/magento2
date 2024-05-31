<?php

namespace Packetery\Checkout\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

class UpgradeData implements UpgradeDataInterface
{
    /** @var \Magento\Config\Model\Config\Factory */
    private $configFactory;

    /** @var \Packetery\Checkout\Model\ResourceModel\Pricingrule\CollectionFactory  */
    private $pricingRuleCollectionFactory;

    /**
     * UpgradeData constructor.
     *
     * @param \Magento\Config\Model\Config\Factory $configFactory
     * @param \Packetery\Checkout\Model\ResourceModel\Pricingrule\CollectionFactory $pricingRuleCollectionFactory
     */
    public function __construct(
        \Magento\Config\Model\Config\Factory $configFactory,
        \Packetery\Checkout\Model\ResourceModel\Pricingrule\CollectionFactory $pricingRuleCollectionFactory
    ) {
        $this->configFactory = $configFactory;
        $this->pricingRuleCollectionFactory = $pricingRuleCollectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ): void {
        if (version_compare($context->getVersion(), "2.1.0", "<")) {
            $configModel = $this->configFactory->create();
            $configModel->setDataByPath('carriers/packetery/sallowspecific', 0); // config option UI was removed
            $configModel->save();
        }

        if (version_compare($context->getVersion(), '2.3.0', '<')) {
            $packeteryOrderTable = $setup->getTable('packetery_order');
            $salesOrderTable = $setup->getTable('sales_order');
            $salesOrderAddressTable = $setup->getTable('sales_order_address');
            $setup->getConnection()->query("
                UPDATE `$packeteryOrderTable`
                JOIN `$salesOrderTable` ON `$salesOrderTable`.`increment_id` = $packeteryOrderTable.`order_number`
                JOIN `$salesOrderAddressTable` ON `$salesOrderTable`.`shipping_address_id` IS NOT NULL AND `$salesOrderAddressTable`.`entity_id` = `$salesOrderTable`.`shipping_address_id`
                SET `$packeteryOrderTable`.`recipient_country_id` = `$salesOrderAddressTable`.`country_id`
                WHERE `$packeteryOrderTable`.`recipient_country_id` IS NULL
            ");

            // Static Vendor groups snapshot for given module version 2.3.0
            $vendorGroupsMapping = [
                'CZ' => '["zpoint","alzabox","zbox"]',
                'SK' => '["zpoint","zbox"]',
                'HU' => '["zpoint","zbox"]',
                'RO' => '["zpoint","zbox"]',
            ];

            $countries = $this->pricingRuleCollectionFactory->create();
            $countries
                ->getSelect()
                ->reset(\Magento\Framework\DB\Select::COLUMNS)
                ->columns('country_id')
                ->group('country_id');
            $pricingCountries = $countries->getColumnValues('country_id');

            foreach ($pricingCountries as $countryId) {
                $vendorGroupsValue = $vendorGroupsMapping[$countryId] ?? null;
                if ($vendorGroupsValue === null) {
                    continue;
                }

                $setup->getConnection()->update(
                    $setup->getTable('packetery_pricing_rule'),
                    [
                        'vendor_groups' => $vendorGroupsValue,
                    ],
                    [
                        '`method` = ?' => 'pickupPointDelivery',
                        '`carrier_code` = ?' => 'packetery',
                        '`country_id` = ?' => $countryId,
                        new \Zend_Db_Expr('`carrier_id` IS NULL'),
                        new \Zend_Db_Expr('`vendor_groups` IS NULL'),
                    ],
                );
            }
        }
    }
}
