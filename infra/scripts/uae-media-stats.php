#!/usr/bin/env php
<?php
$env = include '/var/www/magento/app/etc/env.php';
$db = $env['db']['connection']['default'];
$pdo = new PDO(
    'mysql:host=' . $db['host'] . ';dbname=' . $db['dbname'],
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$stats = $pdo->query('SELECT COUNT(*) FROM catalog_product_entity')->fetchColumn();
$gallery = $pdo->query('SELECT COUNT(*) FROM catalog_product_entity_media_gallery')->fetchColumn();
$linked = $pdo->query('SELECT COUNT(DISTINCT entity_id) FROM catalog_product_entity_media_gallery_value_to_entity')->fetchColumn();
echo "products=$stats gallery=$gallery linked_products=$linked\n";

$row = $pdo->query("
SELECT cpe.sku, mg.value, mgv.position, mgv.disabled
FROM catalog_product_entity cpe
JOIN catalog_product_entity_media_gallery_value_to_entity mgve ON mgve.entity_id = cpe.entity_id
JOIN catalog_product_entity_media_gallery mg ON mg.value_id = mgve.value_id
JOIN catalog_product_entity_media_gallery_value mgv ON mgv.value_id = mg.value_id AND mgv.store_id = 0
WHERE mgv.disabled = 0
ORDER BY cpe.sku, mgv.position
LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);
print_r($row);
