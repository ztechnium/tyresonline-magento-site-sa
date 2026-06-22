<?php

namespace Hdweb\Rfc\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface {

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context) {
        $installer = $setup;
        $installer->startSetup();
        if (version_compare($context->getVersion(), '0.0.2') < 0) {
            $table = $installer->getTable('rfc');
            $columns = [
                'rfc_ip_address' => [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => false,
                    'comment' => 'RFC IP ADDRESS',
                ],
            ];
            $connection = $installer->getConnection();
            foreach ($columns as $name => $definition) {
                $connection->addColumn($table, $name, $definition);
            }
        }
		if (version_compare($context->getVersion(), '0.0.3') < 0) {
				$table = $setup->getConnection()->newTable(
				$setup->getTable('rfc_supplierproducts')
			)->addColumn(
				'supplierproducts_id',
				\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
				null,
				['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
				'Supplier Products Id'
			)->addColumn(
				'supplier_code',
				\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
				255,
				['nullable' => false, 'default' => ''],
				'Supplier Code'
			)->addColumn(
				'item_code',
				\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
				255,
				['nullable' => false, 'default' => ''],
				'Item Code'
			)->addColumn(
				'item_desc',
				\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
				255,
				['nullable' => false, 'default' => ''],
				'Item Desc'
			)->addColumn(
				'item_brand',
				\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
				255,
				['nullable' => false, 'default' => ''],
				'Item Brand'
			)
			->addColumn(
				'item_size',
				\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
				255,
				['nullable' => false, 'default' => ''],
				'Item Size'
			)
			->addColumn(
				'item_runflat',
				\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
				255,
				['nullable' => false, 'default' => ''],
				'Item Runflat'
			)
			->addColumn(
				'item_year',
				\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
				null,
				['unsigned' => true],
				'Item Year'
			)
			->addColumn(
				'item_qty',
				\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
				null,
				['unsigned' => true],
				'Item QTY'
			)
			->addColumn(
				'item_price',
				\Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
				'12,4',
                ['nullable' => true],
                'Item Price'
			)
			->addColumn(
				'item_price2',
				\Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
				'12,4',
                ['nullable' => true],
                'Item Price2'
			)
			->addColumn(
				'item_sell_price',
				\Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
				'12,4',
                ['nullable' => true],
                'Item Sell Price'
			)
			->addColumn(
				'item_offer',
				\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
				255,
				['nullable' => false, 'default' => ''],
				'Item Offer'
			)
			->addColumn(
				'item_origin',
				\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
				255,
				['nullable' => false, 'default' => ''],
				'Item Origin'
			)
			->addColumn(
				'item_load',
				\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
				255,
				['nullable' => false, 'default' => ''],
				'Item Load'
			)
			->addColumn(
				'type',
				\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
				255,
				['nullable' => false, 'default' => ''],
				'Type'
			)
			->addColumn(
				'web_product_id',
				\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
				null,
				['unsigned' => true],
				'Web Product Id'
			)
			->addColumn(
				'web_product_sku',
				\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
				255,
				['nullable' => false, 'default' => ''],
				'Web Product SKU'
			)
			->addColumn(
				'web_product_qty',
				\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
				null,
				['unsigned' => true],
				'Web Product QTY'
			)
			->addColumn(
				'web_product_price',
				\Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
				'12,4',
                ['nullable' => true],
                'Web Product Price'
			)
			->addColumn(
				'web_product_offer',
				\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
				255,
				['nullable' => false, 'default' => ''],
				'Web Product Offer'
			)
			->addColumn(
				'web_product_status',
				\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
				null,
				['unsigned' => true],
				'Web Product Status'
			)
			->addColumn(
				'item_updated_date',
				\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
				255,
				['nullable' => false, 'default' => ''],
				'Item Updated Date'
			)
			->addColumn(
				'item_write_date',
				\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
				255,
				['nullable' => false, 'default' => ''],
				'Item Write Date'
			)
			->addColumn(
				'item_executed_date',
				\Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
				255,
				['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
				'Item Executed Date'
			
			)->setComment(
				'Rfc Supplier Products Table'
			);
			$setup->getConnection()->createTable($table);
        }
		
		if (version_compare($context->getVersion(), '0.0.4') < 0) {
				$table = $setup->getConnection()->newTable(
				$setup->getTable('rfc_master')
				)->addColumn(
				'rfc_master_id',
				\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
				null,
				['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
				'RFC Master Id'
				)->addColumn(
				'rfc_name',
				\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
				255,
				['nullable' => false, 'default' => ''],
				'RFC Name'
				)->addColumn(
				'rfc_url',
				\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
				255,
				['nullable' => false, 'default' => ''],
				'RFC URL'
				)->addColumn(
				'rfc_username',
				\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
				255,
				['nullable' => false, 'default' => ''],
				'RFC Username'
				)->addColumn(
				'rfc_password',
				\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
				255,
				['nullable' => false, 'default' => ''],
				'RFC Password'
				)->addColumn(
				'rfc_database',
				\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
				255,
				['nullable' => false, 'default' => ''],
				'RFC Database Name'
				)->addColumn(
				'rfc_datetime',
				\Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
				255,
				['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
				'RFC Datetime'
				)->addColumn(
				'rfc_enable',
				\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
				255,
				['nullable' => false, 'default' => ''],
				'RFC Enable'
				)->addColumn(
				'rfc_status',
				\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
				255,
				['nullable' => false, 'default' => ''],
				'RFC Status'
				)->addColumn(
				'rfc_last_updated',
				\Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
				255,
				['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
				'RFC Last Updated Datetime'
				)->setComment(
				'Rfc Master Table'
				);
			$setup->getConnection()->createTable($table);
        }
		
		if (version_compare($context->getVersion(), '1.0.0') < 0) {
			$table = $installer->getTable('rfc_master');
            $columns = [
                'rfc_action_url' => [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => false,
                    'default' => '',
                    'comment' => 'RFC Action URL',
                ],
            ];
            $connection = $installer->getConnection();
            foreach ($columns as $name => $definition) {
                $connection->addColumn($table, $name, $definition);
            }
        }
		if (version_compare($context->getVersion(), '1.0.1') < 0) {
			$table = $installer->getTable('rfc');
            $columns = [
                'rfc_response_datetime' => [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    'nullable' => false,
                    'default' => '',
                    'comment' => 'RFC Response Date/Time',
                ],
            ];
            $connection = $installer->getConnection();
            foreach ($columns as $name => $definition) {
                $connection->addColumn($table, $name, $definition);
            }
        }
		
		if (version_compare($context->getVersion(), '1.0.2') < 0) {
			$table = $installer->getTable('rfc_master');
			$columns = [
                'supplier_code' => [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => false,
                    'default' => '',
                    'comment' => 'Supplier Code',
                ],
            ];
            $connection = $installer->getConnection();
            foreach ($columns as $name => $definition) {
                $connection->addColumn($table, $name, $definition);
            }
        }
		if (version_compare($context->getVersion(), '1.0.3') < 0) {
			$table = $installer->getTable('rfc_master');
            $columns = [
                'rfc_expected_records' => [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'nullable' => false,
                    'unsigned' => true,
                    'comment' => 'RFC Executed Records',
                ],
            ];
            $connection = $installer->getConnection();
            foreach ($columns as $name => $definition) {
                $connection->addColumn($table, $name, $definition);
            }
        }
		
		if (version_compare($context->getVersion(), '1.0.4') < 0) {
			$table = $installer->getTable('rfc_master');
			$columns = [
                'attribute_code' => [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => false,
                    'default' => '',
                    'comment' => 'Product Attribute Code',
                ],
            ];
            $connection = $installer->getConnection();
            foreach ($columns as $name => $definition) {
                $connection->addColumn($table, $name, $definition);
            }
        }
		if (version_compare($context->getVersion(), '1.0.5') < 0) {
			$table = $installer->getTable('sales_order');
			$columns = [
                'erp_order_status' => [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'nullable' => true,
                    'default' => 0,
                    'comment' => 'ERP Order Status',
                ],
				'erp_invoice_status' => [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'nullable' => true,
                    'default' => 0,
                    'comment' => 'ERP Invoice Status',
                ],
				'erp_po_status' => [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'nullable' => true,
                    'default' => 0,
                    'comment' => 'ERP Purchase Order Status',
                ]
            ];
			
            $connection = $installer->getConnection();
            foreach ($columns as $name => $definition) {
                $connection->addColumn($table, $name, $definition);
            }
        }
		if (version_compare($context->getVersion(), '1.0.6') < 0) {
            $table   = $installer->getTable('rfc');
            $columns = [
                'requestparam'  => [
                    'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => true,
                    'default'  => '',
                    'comment'  => 'requestparam',
                ],
                'responseparam' => [
                    'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => true,
                    'default'  => '',
                    'comment'  => 'responseparam',
                ],
            ];
            $connection = $installer->getConnection();
            foreach ($columns as $name => $definition) {
                $connection->addColumn($table, $name, $definition);
            }
        }

        $installer->endSetup();
    }

}
