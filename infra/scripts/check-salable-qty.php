<?php
require '/var/www/magento/app/bootstrap.php';
$om = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER)->getObjectManager();
$state = $om->get(\Magento\Framework\App\State::class);
try { $state->setAreaCode('frontend'); } catch (\Exception $e) {}
$pdo = $om->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();
$helper = $om->get(\Hdweb\Tyrefinder\Helper\Productlisting::class);
$rows = $pdo->fetchAll("SELECT cpe.entity_id, cpe.sku, si.qty FROM catalog_product_entity cpe
 JOIN cataloginventory_stock_item si ON si.product_id=cpe.entity_id
 JOIN catalog_category_product cp ON cp.product_id=cpe.entity_id AND cp.category_id=1945
 WHERE si.qty >= 4 LIMIT 10");
foreach ($rows as $r) {
    $salable = (int)$helper->getSalebleQty((int)$r['entity_id']);
    $repo = $om->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
    $p = $repo->getById((int)$r['entity_id'], false, 1);
    echo $r['entity_id'] . ' ' . $r['sku'] . ' db_qty=' . $r['qty'] . ' salable=' . $salable . ' isSalable=' . ($p->isSalable() ? 'yes' : 'no') . PHP_EOL;
}
