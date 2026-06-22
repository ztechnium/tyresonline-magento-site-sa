<?php
/**
 * Ecomteck_StoreLocator extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/mit-license.php
 *
 * @category  Ecomteck
 * @package   Ecomteck_StoreLocator
 * @copyright 2016 Ecomteck
 * @license   http://opensource.org/licenses/mit-license.php MIT License
 * @author    Ecomteck
 */

namespace Ecomteck\StoreLocator\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.Generic.CodeAnalysis.UnusedFunctionParameter)
     */

    // @codingStandardsIgnoreStart
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        if ($installer->tableExists('ecomteck_storelocator_stores')) {
            $table = $installer->getTable('ecomteck_storelocator_stores');
            $connection = $installer->getConnection();
            if (version_compare($context->getVersion(), '2.0.0') < 0) {
                $connection->addColumn(
                    $table,
                    'station',
                    [
                        'type' => Table::TYPE_TEXT,
                        'length' => 255,
                        'nullable' => true,
                        'comment' => 'Nearest Station'
                    ]
                );
                $connection->addColumn(
                    $table,
                    'description',
                    [
                        'type' => Table::TYPE_TEXT,
                        'length' => 255,
                        'nullable' => true,
                        'comment' => 'Description'
                    ]
                );
                $connection->addColumn(
                    $table,
                    'intro',
                    [
                        'type' => Table::TYPE_TEXT,
                        'length' => 255,
                        'nullable' => true,
                        'comment' => 'Intro'
                    ]
                );
                $connection->addColumn(
                    $table,
                    'details_image',
                    [
                        'type' => Table::TYPE_TEXT,
                        'length' => 255,
                        'nullable' => true,
                        'comment' => 'Details Image'
                    ]
                );
                $connection->addColumn(
                    $table,
                    'distance',
                    [
                        'type' => Table::TYPE_TEXT,
                        'length' => 255,
                        'nullable' => true,
                        'comment' => 'Distance'
                    ]
                );
                $connection->addColumn(
                    $table,
                    'external_link',
                    [
                        'type' => Table::TYPE_TEXT,
                        'length' => 255,
                        'nullable' => true,
                        'comment' => 'External Link'
                    ]
                );
            }

            if (version_compare($context->getVersion(), '2.0.1') < 0) {
                $connection->addColumn(
                    $table,
                    'opening_hours',
                    [
                        'type' => Table::TYPE_TEXT,
                        'length' => null,
                        'nullable' => true,
                        'comment' => 'Opening Hours'
                    ]
                );

                $connection->addColumn(
                    $table,
                    'special_opening_hours',
                    [
                        'type' => Table::TYPE_TEXT,
                        'length' => null,
                        'nullable' => true,
                        'comment' => 'Special Opening Hours'
                    ]
                );
            }

            if (version_compare($context->getVersion(), '2.0.2') < 0) {
                // Create table for featured and additional store products
                $table_name = 'ecomteck_storelocator_products';

                $storelocator_products_table = $connection->newTable($installer->getTable($table_name))
                    ->addColumn('stores_id',
                                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                                null,
                                ['nullable' => false, 'unsigned' => true, 'primary' => true],
                                'Store ID')
                    ->addColumn('product_id',
                                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                                null,
                                ['nullable' => false, 'unsigned' => true, 'primary' => true],
                                'Product ID')
                    ->addColumn('position',
                                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                                null,
                                ['nullable' => false, 'default' => '0'],
                                'Position')
                    ->addIndex($installer->getIdxName($table_name,'product_id'),'product_id')
                    ->addForeignKey($setup->getFkName($table_name,'stores_id','ecomteck_storelocator_stores','stores_id'),
                                    'stores_id',
                                    $installer->getTable('ecomteck_storelocator_stores'),
                                    'stores_id',
                                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE)
                    ->addForeignKey($installer->getFkName($table_name,'product_id','catalog_product_entity','entity_id'),
                                    'product_id',
                                    $installer->getTable('catalog_product_entity'),
                                    'entity_id',
                                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE)
                    ->setComment('Catalog Product To Store Linkage Table');

                    $connection->createTable($storelocator_products_table);
            }


            if (version_compare($context->getVersion(), '2.0.3') < 0) {
                $connection->addColumn(
                    $table,
                    'category',
                    [
                        'type' => Table::TYPE_TEXT,
                        'length' => null,
                        'nullable' => true,
                        'comment' => 'Category'
                    ]
                );
            }

            if (version_compare($context->getVersion(), '2.0.4') < 0) {
                $connection->addColumn(
                    $table,
                    'is_all_products',
                    [
                        'type' => Table::TYPE_BOOLEAN,
                        'length' => null,
                        'nullable' => true,
                        'default'   => '1',
                        'comment' => 'All Products Available For This Store'
                    ]
                );
            }
			if (version_compare($context->getVersion(), '2.0.5') < 0) {
                $connection->addColumn(
                    $table,
                    'ismobilevan',
                    [
                        'type' => Table::TYPE_TEXT,
                        'length' => 255,
                        'nullable' => true,
                        'comment' => 'Ismobilevan'
                    ]
                );
                $connection->addColumn(
                    $table,
                    'shipping_amount',
                    [
                        'type' => Table::TYPE_TEXT,
                        'length' => 255,
                        'nullable' => true,
                        'comment' => 'Shipping Amount'
                    ]
                );
				$connection->addColumn(
                    $table,
                    'position',
                    [
                        'type' => Table::TYPE_TEXT,
                        'length' => 255,
                        'nullable' => true,
                        'comment' => 'Position'
                    ]
                );
                $connection->addColumn(
                    $table,
                    'pickup_service',
                    [
                        'type' => Table::TYPE_TEXT,
                        'length' => 255,
                        'nullable' => true,
						'default'   => '0',
                        'comment' => 'Is Pickup Service'
                    ]
                );
				$connection->addColumn(
                    $table,
                    'email_copy',
                    [
                        'type' => Table::TYPE_TEXT,
                        'length' => 255,
                        'nullable' => true,
                        'comment' => 'Email Copy'
                    ]
                );
             
            }

            if (version_compare($context->getVersion(), '2.0.6') < 0) {
                $connection->addColumn(
                    $table,
                    'opening_hours_text1',
                    [
                        'type' => Table::TYPE_TEXT,
                        'length' => 255,
                        'nullable' => true,
                        'comment' => 'Opening Hours Text1'
                    ]
                );
                $connection->addColumn(
                    $table,
                    'opening_hours_text2',
                    [
                        'type' => Table::TYPE_TEXT,
                        'length' => 255,
                        'nullable' => true,
                        'comment' => 'Opening Hours Text2'
                    ]
                );
            }
			if (version_compare($context->getVersion(), '2.0.7') < 0) {
                $connection->addColumn(
                    $table,
                    'skip_days',
                    [
                        'type' => Table::TYPE_TEXT,
                        'length' => 255,
                        'nullable' => true,
                        'comment' => 'Skip Days',
						'default'   => '0'
                    ]
                );
            }

            if (version_compare($context->getVersion(), '2.0.8') < 0) {
                $connection->addColumn(
                    $table,
                    'name_rtl',
                    [
                        'type' => Table::TYPE_TEXT,
                        'length' => 255,
                        'nullable' => true,
                        'comment' => 'Name Rtl',
                    ]
                );
                $connection->addColumn(
                    $table,
                    'address_rtl',
                    [
                        'type' => Table::TYPE_TEXT,
                        'length' => 255,
                        'nullable' => true,
                        'comment' => 'Address Rtl',
                    ]
                );
                $connection->addColumn(
                    $table,
                    'city_rtl',
                    [
                        'type' => Table::TYPE_TEXT,
                        'length' => 255,
                        'nullable' => true,
                        'comment' => 'City Rtl',
                    ]
                );
                $connection->addColumn(
                    $table,
                    'opening_hours_text1_rtl',
                    [
                        'type' => Table::TYPE_TEXT,
                        'length' => 255,
                        'nullable' => true,
                        'comment' => 'Opening Hours Text1 Rtl',
                    ]
                );
                $connection->addColumn(
                    $table,
                    'opening_hours_text2_rtl',
                    [
                        'type' => Table::TYPE_TEXT,
                        'length' => 255,
                        'nullable' => true,
                        'comment' => 'Opening Hours Text2 Rtl',
                    ]
                );
            }
            $installer->endSetup();
        }
    }
}