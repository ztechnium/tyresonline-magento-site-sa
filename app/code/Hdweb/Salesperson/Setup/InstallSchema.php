<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Hdweb\Salesperson\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        $connection = $installer->getConnection();

        if ($connection->tableColumnExists('sales_order', 'sales_person_id') === false) {
            $connection
                ->addColumn(
                    $setup->getTable('sales_order'),
                    'sales_person_id',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'null',
                        ['nullable' => true],
                       'comment' => 'Sales Person Id'
                    ]
                );
        }
        $installer->endSetup();
    }
}
