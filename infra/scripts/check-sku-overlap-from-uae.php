#!/usr/bin/env php
<?php
declare(strict_types=1);

$env = include '/var/www/magento/app/etc/env.php';
$db = $env['db']['connection']['default'];
$uae = new PDO('mysql:host='.$db['host'].';dbname='.$db['dbname'], $db['username'], $db['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$ksaHost = 'tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com';
$ksa = new PDO('mysql:host='.$ksaHost.';dbname=tyresonline_sa', 'magento', 'uxaYMIQRwEU0AFl1Ck69LJnH', [PDO::ATTR_TIMEOUT => 5, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$ksaSkus = $ksa->query('SELECT sku FROM catalog_product_entity')->fetchAll(PDO::FETCH_COLUMN);
$placeholders = implode(',', array_fill(0, count($ksaSkus), '?'));
$sql = "
SELECT cpe.sku, COUNT(mg.value_id) AS img_count
FROM catalog_product_entity cpe
JOIN catalog_product_entity_media_gallery_value_to_entity mgve ON mgve.entity_id = cpe.entity_id
WHERE cpe.sku IN ($placeholders)
GROUP BY cpe.sku
";
$stmt = $uae->prepare($sql);
$stmt->execute($ksaSkus);
$matched = $stmt->rowCount();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo 'ksa_total=' . count($ksaSkus) . ' matched_in_uae=' . count($rows) . "\n";
$totalImgs = array_sum(array_column($rows, 'img_count'));
echo "total_images_to_import=$totalImgs\n";
