#!/usr/bin/env php
<?php
declare(strict_types=1);

$skuFile = $argv[1] ?? '/tmp/ksa-skus.txt';
if (!is_readable($skuFile)) {
    fwrite(STDERR, "SKU file not found: $skuFile\n");
    exit(1);
}
$skus = array_values(array_filter(array_map('trim', file($skuFile, FILE_IGNORE_NEW_LINES))));
if (!$skus) {
    fwrite(STDERR, "No SKUs in file\n");
    exit(1);
}

$env = include '/var/www/magento/app/etc/env.php';
$db = $env['db']['connection']['default'];
$pdo = new PDO('mysql:host='.$db['host'].';dbname='.$db['dbname'], $db['username'], $db['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$matched = 0;
$totalImages = 0;
$chunkSize = 500;
foreach (array_chunk($skus, $chunkSize) as $chunk) {
    $ph = implode(',', array_fill(0, count($chunk), '?'));
    $sql = "
    SELECT cpe.sku, COUNT(mg.value_id) AS img_count
    FROM catalog_product_entity cpe
    JOIN catalog_product_entity_media_gallery_value_to_entity mgve ON mgve.entity_id = cpe.entity_id
    JOIN catalog_product_entity_media_gallery mg ON mg.value_id = mgve.value_id
    WHERE cpe.sku IN ($ph)
    GROUP BY cpe.sku
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($chunk);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $matched++;
        $totalImages += (int)$row['img_count'];
    }
}
echo "ksa_skus=" . count($skus) . " matched_in_uae=$matched total_images=$totalImages\n";
