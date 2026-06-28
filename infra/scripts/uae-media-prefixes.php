#!/usr/bin/env php
<?php
$env = include '/var/www/magento/app/etc/env.php';
$db = $env['db']['connection']['default'];
$pdo = new PDO('mysql:host='.$db['host'].';dbname='.$db['dbname'], $db['username'], $db['password']);

$rows = $pdo->query("
SELECT DISTINCT
  CASE
    WHEN mg.value LIKE '/catalog/product/%' THEN 'catalog/product'
    WHEN mg.value LIKE '/%' THEN TRIM(BOTH '/' FROM SUBSTRING_INDEX(TRIM(LEADING '/' FROM mg.value), '/', 1))
    ELSE 'other'
  END AS prefix,
  COUNT(*) AS cnt
FROM catalog_product_entity_media_gallery mg
GROUP BY prefix
ORDER BY cnt DESC
LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo $r['prefix'] . "\t" . $r['cnt'] . "\n";
}

$tyre = $pdo->query("
SELECT COUNT(DISTINCT cpe.sku) 
FROM catalog_product_entity cpe
JOIN catalog_product_entity_media_gallery_value_to_entity mgve ON mgve.entity_id = cpe.entity_id
WHERE cpe.sku LIKE 'TYO-%'
")->fetchColumn();
echo "uae_tyo_skus_with_images=$tyre\n";
