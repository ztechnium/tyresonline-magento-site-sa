<?php
require '/var/www/magento/app/bootstrap.php';
$p = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER)
    ->getObjectManager()->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();
$rows = $p->fetchAll(
    "SELECT cpe.entity_id, cpe.sku, si.qty, ur.request_path
     FROM catalog_product_entity cpe
     JOIN cataloginventory_stock_item si ON si.product_id = cpe.entity_id
     JOIN cataloginventory_stock_status st ON st.product_id = cpe.entity_id AND st.stock_status = 1
     JOIN catalog_category_product cp ON cp.product_id = cpe.entity_id AND cp.category_id = 1945
     JOIN url_rewrite ur ON ur.entity_id = cpe.entity_id AND ur.entity_type = 'product' AND ur.store_id = 1 AND ur.redirect_type = 0
     WHERE si.qty >= 2
     ORDER BY si.qty DESC LIMIT 5"
);
foreach ($rows as $r) {
    echo $r['entity_id'] . '|' . $r['sku'] . '|qty=' . $r['qty'] . '|' . $r['request_path'] . PHP_EOL;
}
