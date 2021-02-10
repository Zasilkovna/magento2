<?php


namespace Packetery\Checkout\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{

    /**
     * {@inheritdoc}
     */
    public function upgrade(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $connection = $setup->getConnection();
        $connection->startSetup();

        if (version_compare($context->getVersion(), "2.0.3", "<")) {
            $sql = "
ALTER TABLE `packetery_order`
ADD COLUMN `is_carrier` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Is Point_id ID of external carrier?' AFTER `point_name`;";

            $connection->query($sql);

            $sql = "
ALTER TABLE `packetery_order`
ADD COLUMN `carrier_pickup_point` VARCHAR(40) NULL COMMENT 'External carrier pickup point ID' AFTER `is_carrier`;";

            $connection->query($sql);
        }

        $connection->endSetup();
    }
}
