<?php

namespace Hdweb\Shippingform\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface {

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context) {
        $installer = $setup;
        $installer->startSetup();
		if (version_compare($context->getVersion(), '1.0.0') < 0) {
            $eavTable = $installer->getTable('sales_order');
            $columns = [
                 'pickup_type' => [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => true,
                    'comment' => 'Pickup Type',
                ],
                 'pickup_location' => [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => true,
                    'comment' => 'Pickup Location',
                ]
            ];
            $connection = $installer->getConnection();
            foreach ($columns as $name => $definition) {
                $connection->addColumn($eavTable, $name, $definition);
            }
			
			$eavTable = $installer->getTable('quote');
            $columns = [
                 'pickup_type' => [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => true,
                    'comment' => 'Pickup Type',
                ],
                 'pickup_location' => [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => true,
                    'comment' => 'Pickup Location',
                ]
            ];
            $connection = $installer->getConnection();
            foreach ($columns as $name => $definition) {
                $connection->addColumn($eavTable, $name, $definition);
            }
			
			$setup->getConnection()
				->addColumn(
					$setup->getTable('sales_order'),
					'pickupavailable',
					[
						'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
						'default'  => 0,
						'nullable' => true,
						'comment'  => 'pickupavailable',

					]
				);
        }
        
        $installer->endSetup();
    }
}