<?php

namespace Hdweb\Booking\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        if (version_compare($context->getVersion(), '1.0.2') < 0) {

            $installer->getConnection()->addColumn(
                $installer->getTable('hdweb_bookings'),
                'admincomment',
                [
                    'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => false,
                    'comment'  => 'Admin Comment',
                ]
            );

            $installer->getConnection()->addColumn(
                $installer->getTable('hdweb_bookings'),
                'logs',
                [
                    'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => false,
                    'comment'  => 'Logs',
                ]
            );
        }

        $installer->endSetup();
    }

}
