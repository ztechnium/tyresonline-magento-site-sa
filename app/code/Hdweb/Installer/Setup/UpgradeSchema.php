<?php

namespace Hdweb\Installer\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface {

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context) {
        $installer = $setup;
        $installer->startSetup();
		 if (version_compare($context->getVersion(), '1.0.1') < 0) {
			$eavTable = $installer->getTable('sales_order');
			$columns = [
				 'mobilevanservice_location' => [
					'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					'nullable' => true,
					'comment' => 'Mobile VAN Service Location',
				]
			];
			$connection = $installer->getConnection();
			foreach ($columns as $name => $definition) {
				$connection->addColumn($eavTable, $name, $definition);
			}
			
			$eavTable = $installer->getTable('quote');
			$columns = [
				 'mobilevanservice_location' => [
					'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					'nullable' => true,
					'comment' => 'Mobile VAN Service Location',
				]
			];
			$connection = $installer->getConnection();
			foreach ($columns as $name => $definition) {
				$connection->addColumn($eavTable, $name, $definition);
			}
		}
        $installer->endSetup();
    }
}