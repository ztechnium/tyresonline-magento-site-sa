<?php

namespace Hdweb\Tyrefinder\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements \Magento\Framework\Setup\InstallSchemaInterface
{

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        $conn = $setup->getConnection();

        $tableName = $setup->getTable('installer_brand_rim');
        if ($conn->isTableExists($tableName) != true) {
            $table = $conn->newTable($tableName)
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true]
                )
                ->addColumn(
                    'installerid',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => true, 'default' => '']
                )
                ->addColumn(
                    'brand',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => true, 'default' => '']
                )
                ->addColumn(
                    'rim',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => true, 'default' => '']
                )
                ->addColumn(
                    'qty',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => true, 'default' => '']
                )
                ->addColumn(
                    'shipping_amount',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => true, 'default' => '']
                )
                ->addColumn(
                    'status',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => true, 'default' => '']
                )
                ->addColumn(
                    'startdate',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => true, 'default' => '']
                )
                ->addColumn(
                    'enddate',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => true, 'default' => '']
                );
            $conn->createTable($table);
        }
        $installer->endSetup();
    }

}
