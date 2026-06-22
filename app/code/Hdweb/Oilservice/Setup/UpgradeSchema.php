<?php
namespace Hdweb\Oilservice\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
 
class UpgradeSchema implements UpgradeSchemaInterface {
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context) {
		$installer = $setup;
        $installer->startSetup();
		if (version_compare($context->getVersion(), '1.0.1') < 0) {
        $table = $setup->getConnection()->newTable(
        $setup->getTable('hdweb_user_vehicle_info')
				)->addColumn(
					'id',
					\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
					null,
					['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
					'User Vehicle Info Id'
				)->addColumn(
					'customer_id',
					\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
					null,
					['nullable' => true],
					'Customer Id'
				)->addColumn(
					'customer_email',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable' => true, 'default' => ''],
					'Customer Email'
				)->addColumn(
					'user_vehicle_key',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable' => true, 'default' => ''],
					'User Vehicle Key'
				)->addColumn(
					'vehicle_detail',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					'',
					['nullable' => true, 'default' => ''],
					'Vehicle Detail'
				)->addColumn(
					'status',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable' => true, 'default' => 1],
					'Status'
				)->addColumn(
					'created_at',
					\Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
					null,
					['nullable' => true, 'default'  => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE],
					'Creation Date'
				)->addColumn(
					'updated_at',
					\Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
					null,
					['nullable' => true, 'default'  => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE],
					'Updation Date'
				)->setComment(
					'User Vehicle Info Table'
				);
			$setup->getConnection()->createTable($table);
		}
		
		$installer->endSetup();
    }
}