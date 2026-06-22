<?php

namespace Hdweb\Oilservice\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
 
class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
		$installer->startSetup();
		if (!$installer->tableExists('hdweb_autoparts')){
			$table = $installer->getConnection()
				->newTable($installer->getTable('hdweb_autoparts'))
				->addColumn(
					'autoparts_id',
					\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
					null,
					['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
					'Autoparts Id'
				)->addColumn(
					'make',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable' => true, 'default' => ''],
					'Make'
				)->addColumn(
					'model',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable' => true, 'default' => ''],
					'Model'
				)->addColumn(
					'year',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable' => true, 'default' => ''],
					'Year'
				)->addColumn(
					'make_model_year',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable' => true, 'default' => ''],
					'Make-Model-Year'
				)->addColumn(
					'generation',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable' => true, 'default' => ''],
					'Generation'
				)->addColumn(
					'power',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable' => true, 'default' => ''],
					'Power'
				)->addColumn(
					'engine',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable' => true, 'default' => ''],
					'Engine'
				)->addColumn(
					'engine_type',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable' => true, 'default' => ''],
					'Engine Type'
				)->addColumn(
					'engine_code',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable' => true, 'default' => ''],
					'Engine Code'
				)->addColumn(
					'fuel',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable' => true, 'default' => ''],
					'Fuel'
				)->addColumn(
					'engine_capacity',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable' => true, 'default' => ''],
					'Engine Capacity'
				)->addColumn(
					'oil_grade',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable' => true, 'default' => ''],
					'Oil Grade'
				)->addColumn(
					'oil_filter_part_number',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable' => true, 'default' => ''],
					'Oil Filter Part Number'
				)->addColumn(
					'air_filter_part_number',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable' => true, 'default' => ''],
					'Air Filter Part Number'
				)->addColumn(
					'ac_filter_part_number',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable' => true, 'default' => ''],
					'AC Filter Part Number'
				)->addColumn(
					'service_type',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable' => true, 'default' => ''],
					'Service Type'
				)->addColumn(
					'centre_bore',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable' => true, 'default' => ''],
					'Centre Bore'
				)->addColumn(
					'bolt_pattern',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable' => true, 'default' => ''],
					'Bolt Pattern'
				)->addColumn(
					'rim',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable' => true, 'default' => ''],
					'Rim Size'
				)->addColumn(
					'rim_diameter',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable' => true, 'default' => ''],
					'Rim Diameter'
				)->addColumn(
					'rim_width',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable' => true, 'default' => ''],
					'Rim Width'
				)->addColumn(
					'rim_offset',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable' => true, 'default' => ''],
					'Rim Offset'
				)->addColumn(
					'tire',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable' => true, 'default' => ''],
					'Tire Size'
				)->addColumn(
					'tire_width',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable' => true, 'default' => ''],
					'Tire Width'
				)->addColumn(
					'tire_aspect_ratio',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable' => true, 'default' => ''],
					'Tire Aspect Ratio'
				)->addColumn(
					'load_index',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable' => true, 'default' => ''],
					'Load Index'
				)->addColumn(
					'speed_index',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable' => true, 'default' => ''],
					'Speed Index'
				)->addColumn(
					'is_runflat_tires',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable' => true, 'default' => ''],
					'Is Runflat Tires'
				)->addColumn(
					'title',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable' => true, 'default' => ''],
					'Title'
				)->addColumn(
					'image',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable' => true, 'default' => ''],
					'Image'
				)->addColumn(
					'mapping_id',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable' => true, 'default' => ''],
					'Mapping Id'
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
					'Hdweb Autoparts Table'
				);
			$setup->getConnection()->createTable($table);
		}	
		if (!$installer->tableExists('hdweb_oilgrade')){
			$table = $installer->getConnection()
				->newTable($installer->getTable('hdweb_oilgrade'))
				->addColumn(
					'oilgrade_id',
					\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
					null,
					['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
					'Oil Grade Id'
				)->addColumn(
					'make_id',
					\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
					null,
					['nullable' => true],
					'Make Id'
				)->addColumn(
					'model_id',
					\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
					null,
					['nullable' => true],
					'Model Id'
				)->addColumn(
					'engine_id',
					\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
					null,
					['nullable' => true],
					'Engine Id'
				)->addColumn(
					'make',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable' => true, 'default' => ''],
					'Make'
				)->addColumn(
					'model',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable' => true, 'default' => ''],
					'Model'
				)->addColumn(
					'engine',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable' => true, 'default' => ''],
					'Engine'
				)->addColumn(
					'oil_litre',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable' => true, 'default' => ''],
					'Oil Litre'
				)->addColumn(
					'mapping_id',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable' => true, 'default' => ''],
					'Mapping Id'
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
					'Hdweb Oil Grade Table'
				);
			$setup->getConnection()->createTable($table);
		}
			
        $setup->endSetup();
    }
}