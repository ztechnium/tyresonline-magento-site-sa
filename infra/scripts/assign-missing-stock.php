<?php
use Magento\Framework\App\Bootstrap;

require '/var/www/magento/app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$om->get(\Magento\Framework\App\State::class)->setAreaCode('adminhtml');

$connection = $om->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();

// Products missing stock rows
$missing = $connection->fetchCol("
  SELECT p.entity_id FROM catalog_product_entity p
  LEFT JOIN cataloginventory_stock_item s ON p.entity_id = s.product_id
  WHERE s.product_id IS NULL
");

$stockRegistry = $om->get(\Magento\CatalogInventory\Api\StockRegistryInterface::class);
$sourceItemsSave = $om->get(\Magento\InventoryApi\Api\SourceItemsSaveInterface::class);
$sourceItemFactory = $om->get(\Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory::class);

$count = 0;
foreach ($missing as $productId) {
    $sku = $connection->fetchOne('SELECT sku FROM catalog_product_entity WHERE entity_id = ?', [$productId]);
    if (!$sku) continue;

    $stockItem = $stockRegistry->getStockItem($productId);
    $stockItem->setQty(5);
    $stockItem->setIsInStock(1);
    $stockRegistry->updateStockItemBySku($sku, $stockItem);

    $sourceItem = $sourceItemFactory->create();
    $sourceItem->setSku($sku);
    $sourceItem->setSourceCode('default');
    $sourceItem->setQuantity(5);
    $sourceItem->setStatus(1);
    $sourceItemsSave->execute([$sourceItem]);
    $count++;
    if ($count % 100 === 0) echo "Fixed $count\n";
}
echo "Assigned stock to $count products\n";
