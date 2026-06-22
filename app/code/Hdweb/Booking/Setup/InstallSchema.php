<?php

namespace Hdweb\Booking\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
 
class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
		$installer->startSetup();	
		if (!$installer->tableExists('hdweb_bookings')){
			$table = $installer->getConnection()
				->newTable($installer->getTable('hdweb_bookings'))
				->addColumn(
					'appointment_id',
					\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
					null,
					['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
					'Id'
				)
				->addColumn(
					'fname',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					null,
					['nullable' => true, 'default' => null],
					'First Name'
				)
				->addColumn(
					'lname',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					null,
					['nullable' => true, 'default' => null],
					'Last Name'
				)
				->addColumn(
					'phonenumber',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					null,
					['nullable' => true, 'default' => null],
					'Phone Number'
				)
				->addColumn(
					'email',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					null,
					['nullable' => true, 'default' => null],
					'Email'
				)
				->addColumn(
					'date',
					\Magento\Framework\DB\Ddl\Table::TYPE_DATE,
					null,
					['nullable' => true, 'default' => null],
					'Appointment Date'
				)
				->addColumn(
					'make',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					null,
					['nullable' => true, 'default' => null],
					'Make'
				)
				->addColumn(
					'model',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					null,
					['nullable' => true, 'default' => null],
					'Model'
				)
				->addColumn(
					'year',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					null,
					['nullable' => true, 'default' => null],
					'Year'
				)
				->addColumn(
					'vin',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					null,
					['nullable' => true, 'default' => null],
					'VIN Number'
				)
				->addColumn(
					'service',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					null,
					['nullable' => true, 'default' => null],
					'Service'
				)
				->addColumn(
					'message',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					null,
					['nullable' => true, 'default' => null],
					'Message'
				)
				->addColumn(
					'status',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					null,
					['nullable' => true, 'default' => null],
					'Status'
				)
				->addColumn(
					'created_at',
					\Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
					null,
					['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
					'Created At'
				)
				->addColumn(
					'updated_at',
					\Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
					null,
					['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE],
					'Updated At'
				)->setComment(
					'Hdweb Booking'
				);
			$setup->getConnection()->createTable($table);
		}
			
        $setup->endSetup();
    }
}