<?php

namespace Mageplaza\BannerSlider\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        if (version_compare($context->getVersion(), '2.0.1', '<')) {
            // Add new columns
            $table = $installer->getTable('mageplaza_bannerslider_banner');
            $connection = $installer->getConnection();

            if ($connection->isTableExists($table) && !$connection->tableColumnExists($table, 'from_date')) {
                $connection->addColumn(
                    $table,
                    'from_date',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                        'nullable' => true,
                        'comment' => 'From Date'
                    ]
                );
            }

            if ($connection->isTableExists($table) && !$connection->tableColumnExists($table, 'to_date')) {
                $connection->addColumn(
                    $table,
                    'to_date',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                        'nullable' => true,
                        'comment' => 'To Date'
                    ]
                );
            }

            if ($connection->isTableExists($table) && !$connection->tableColumnExists($table, 'sort_order')) {
                $connection->addColumn(
                    $table,
                    'sort_order',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'nullable' => true,
                        'comment' => 'Sort Order'
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '2.0.2', '<')) {
            $table = $installer->getTable('mageplaza_bannerslider_banner');
            $connection = $installer->getConnection();
            if ($connection->isTableExists($table) && !$connection->tableColumnExists($table, 'meta_title')) {
                $connection->addColumn(
                    $table,
                    'meta_title',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => true,
                        'comment' => 'Meta Title'
                    ]
                );
            }
            if ($connection->isTableExists($table) && !$connection->tableColumnExists($table, 'meta_description')) {
                $connection->addColumn(
                    $table,
                    'meta_description',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => true,
                        'comment' => 'Meta Description'
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '2.0.3', '<')) {
            $table = $installer->getTable('mageplaza_bannerslider_banner');
            $connection = $installer->getConnection();
            if ($connection->isTableExists($table) && !$connection->tableColumnExists($table, 'category')) {
                $connection->addColumn(
                    $table,
                    'category',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => true,
                        'length' => 50,
                        'comment' => 'Category'
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '2.0.4', '<')) {
            $table = $installer->getTable('mageplaza_bannerslider_banner');
            $connection = $installer->getConnection();
            if ($connection->isTableExists($table) && !$connection->tableColumnExists($table, 'banner_image_two')) {
                $connection->addColumn(
                    $table,
                    'banner_image_two',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => true,
                        'comment' => 'Banner Image Two'
                    ]
                );
            }
            if ($connection->isTableExists($table) && !$connection->tableColumnExists($table, 'brand_logo')) {
                $connection->addColumn(
                    $table,
                    'brand_logo',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => true,
                        'comment' => 'Brand Logo'
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '2.0.5', '<')) {
            $table = $installer->getTable('mageplaza_bannerslider_banner');
            $connection = $installer->getConnection();
            if ($connection->isTableExists($table) && !$connection->tableColumnExists($table, 'banner_image_three')) {
                $connection->addColumn(
                    $table,
                    'banner_image_three',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => true,
                        'comment' => 'Banner Image Three'
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '2.0.6', '<')) {
            $table = $installer->getTable('mageplaza_bannerslider_banner');
            $connection = $installer->getConnection();
            if ($connection->isTableExists($table) && !$connection->tableColumnExists($table, 'name_ar')) {
                $connection->addColumn(
                    $table,
                    'name_ar',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => true,
                        'length' => 255,
                        'comment' => 'Name Ar'
                    ]
                );
            }

            if ($connection->isTableExists($table) && !$connection->tableColumnExists($table, 'content_ar')) {
                $connection->addColumn(
                    $table,
                    'content_ar',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => true,
                        'length' => '64k',
                        'comment' => 'Content Ar'
                    ]
                );
            }

            if ($connection->isTableExists($table) && !$connection->tableColumnExists($table, 'image_ar')) {
                $connection->addColumn(
                    $table,
                    'image_ar',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => true,
                        'length' => 255,
                        'comment' => 'Image Ar'
                    ]
                );
            }

            if ($connection->isTableExists($table) && !$connection->tableColumnExists($table, 'title_ar')) {
                $connection->addColumn(
                    $table,
                    'title_ar',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => true,
                        'length' => 255,
                        'comment' => 'Title Ar'
                    ]
                );
            }

            if ($connection->isTableExists($table) && !$connection->tableColumnExists($table, 'meta_title_ar')) {
                $connection->addColumn(
                    $table,
                    'meta_title_ar',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => true,
                        'comment' => 'Meta Title Ar'
                    ]
                );
            }

            if ($connection->isTableExists($table) && !$connection->tableColumnExists($table, 'meta_description_ar')) {
                $connection->addColumn(
                    $table,
                    'meta_description_ar',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => true,
                        'comment' => 'Meta Description Ar'
                    ]
                );
            }

            if ($connection->isTableExists($table) && !$connection->tableColumnExists($table, 'banner_image_two_ar')) {
                $connection->addColumn(
                    $table,
                    'banner_image_two_ar',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => true,
                        'comment' => 'Banner Image Two Ar'
                    ]
                );
            }

            if ($connection->isTableExists($table) && !$connection->tableColumnExists($table, 'banner_image_three_ar')) {
                $connection->addColumn(
                    $table,
                    'banner_image_three_ar',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => true,
                        'comment' => 'Banner Image Three Ar'
                    ]
                );
            }
        }

        $installer->endSetup();
    }
}
