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
        $setup->startSetup();
        if (version_compare($context->getVersion(), "2.1.0", "<")) {
            $table = $setup->getTable('packetery_order');
            if ($setup->getConnection()->isTableExists($table) == true) {

                $connection = $setup->getConnection();
                
				$connection->addColumn(
                    $table,
                    'barcode',
                    [
						'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
						'length' => 120,
						'nullable' => true,
						'comment' => 'barcode',
					]
                    
                );
            }
        }
        $setup->endSetup();
    }
}
