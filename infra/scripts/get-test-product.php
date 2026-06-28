#!/usr/bin/env php
<?php
require '/var/www/magento/app/bootstrap.php';
$p = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER)
    ->getObjectManager()->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();
$row = $p->fetchRow("SELECT cpe.entity_id, ur.request_path FROM catalog_product_entity cpe
 JOIN cataloginventory_stock_status st ON st.product_id=cpe.entity_id AND st.stock_status=1
 JOIN cataloginventory_stock_item si ON si.product_id=cpe.entity_id AND si.qty >= 2
 JOIN catalog_category_product cp ON cp.product_id=cpe.entity_id AND cp.category_id=1945
 JOIN url_rewrite ur ON ur.entity_id=cpe.entity_id AND ur.entity_type='product' AND ur.store_id=1 AND ur.redirect_type=0
 ORDER BY si.qty DESC
 LIMIT 1");
echo ($row['entity_id'] ?? '') . '|' . ($row['request_path'] ?? '');
