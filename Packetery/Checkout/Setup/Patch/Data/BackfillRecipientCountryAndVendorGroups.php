<?php

declare(strict_types=1);

namespace Packetery\Checkout\Setup\Patch\Data;

use Magento\Framework\DB\Select;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Packetery\Checkout\Model\ResourceModel\Pricingrule\CollectionFactory;

/** getVersion() only guards re-run on existing installs; it is not the module version */
class BackfillRecipientCountryAndVendorGroups implements DataPatchInterface, PatchVersionInterface
{
    // Static Vendor groups snapshot for given module version 2.3.0
    /** @var array<string, string> */
    private const VENDOR_GROUPS_MAPPING = [
        'CZ' => '["zpoint","zbox"]',
        'SK' => '["zpoint","zbox"]',
        'HU' => '["zpoint","zbox"]',
        'RO' => '["zpoint","zbox"]',
    ];

    /** @var ModuleDataSetupInterface */
    private $moduleDataSetup;

    /** @var CollectionFactory */
    private $pricingRuleCollectionFactory;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CollectionFactory $pricingRuleCollectionFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->pricingRuleCollectionFactory = $pricingRuleCollectionFactory;
    }

    public static function getDependencies(): array
    {
        return [DisableAllowSpecific::class];
    }

    public function getAliases(): array
    {
        return [];
    }

    public static function getVersion(): string
    {
        return '2.4.0';
    }

    public function apply(): void
    {
        $setup = $this->moduleDataSetup;
        $connection = $setup->getConnection();

        $packeteryOrderTable = $setup->getTable('packetery_order');
        $salesOrderTable = $setup->getTable('sales_order');
        $salesOrderAddressTable = $setup->getTable('sales_order_address');
        $connection->query("
            UPDATE `$packeteryOrderTable`
            JOIN `$salesOrderTable` ON `$salesOrderTable`.`increment_id` = $packeteryOrderTable.`order_number`
            JOIN `$salesOrderAddressTable` ON `$salesOrderTable`.`shipping_address_id` IS NOT NULL AND `$salesOrderAddressTable`.`entity_id` = `$salesOrderTable`.`shipping_address_id`
            SET `$packeteryOrderTable`.`recipient_country_id` = `$salesOrderAddressTable`.`country_id`
            WHERE `$packeteryOrderTable`.`recipient_country_id` IS NULL
        ");

        $countries = $this->pricingRuleCollectionFactory->create();
        $countries
            ->getSelect()
            ->reset(Select::COLUMNS)
            ->columns('country_id')
            ->group('country_id');
        $pricingCountries = $countries->getColumnValues('country_id');

        foreach ($pricingCountries as $countryId) {
            $vendorGroupsValue = self::VENDOR_GROUPS_MAPPING[$countryId] ?? null;
            if ($vendorGroupsValue === null) {
                continue;
            }

            $connection->update(
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
                ]
            );
        }

        $pricingRuleTable = $setup->getTable('packetery_pricing_rule');
        $carrierTable = $setup->getTable('packetery_carrier');
        if ($connection->isTableExists($carrierTable) && $connection->tableColumnExists($carrierTable, 'carrier_name')) {
            $connection->query("
                UPDATE `$pricingRuleTable` pr
                INNER JOIN `$carrierTable` c ON c.carrier_id = pr.carrier_id AND pr.carrier_id IS NOT NULL
                SET pr.carrier_name = c.carrier_name
                WHERE c.carrier_name IS NOT NULL AND c.carrier_name != ''
            ");
            $connection->dropColumn($carrierTable, 'carrier_name');
        }
    }
}
