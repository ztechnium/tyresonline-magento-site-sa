<?php

namespace Hdweb\Vehicles\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface {

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context) {
        $installer = $setup;
        $installer->startSetup();

        if (version_compare($context->getVersion(), '1.0.1') < 0) {
            $table = $installer->getTable('hdweb_vehicles');
            $columns = [
                'make_top' => [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => '2M',
                    'nullable' => true,
                    'comment' => 'Make Top',
                ],
                'make_bottom' => [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => '2M',
                    'nullable' => true,
                    'comment' => 'Make Bottom',
                ],
                'model_top' => [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => '2M',
                    'nullable' => true,
                    'comment' => 'Model Top',
                ]
            ];
            
            $connection = $installer->getConnection();
            foreach ($columns as $name => $definition) {
                $connection->addColumn($table, $name, $definition);
            }
        }
        
        $installer->endSetup();
    }

}
