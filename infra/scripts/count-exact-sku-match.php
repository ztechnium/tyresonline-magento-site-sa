#!/usr/bin/env php
<?php
declare(strict_types=1);
$skuFile = '/tmp/ksa-skus.txt';
$skus = file($skuFile, FILE_IGNORE_NEW_LINES);
$env = include '/var/www/magento/app/etc/env.php';
$db = $env['db']['connection']['default'];
$pdo = new PDO('mysql:host='.$db['host'].';dbname='.$db['dbname'], $db['username'], $db['password']);

$matched = 0;
foreach (array_chunk($skus, 500) as $chunk) {
    $ph = implode(',', array_fill(0, count($chunk), '?'));
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT sku) FROM catalog_product_entity WHERE sku IN ($ph)");
    $stmt->execute($chunk);
    $matched += (int)$stmt->fetchColumn();
}
echo "exact_sku_matches=$matched / " . count($skus) . "\n";
