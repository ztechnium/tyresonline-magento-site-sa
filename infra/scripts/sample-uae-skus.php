#!/usr/bin/env php
<?php
$env = include '/var/www/magento/app/etc/env.php';
$db = $env['db']['connection']['default'];
$pdo = new PDO('mysql:host='.$db['host'].';dbname='.$db['dbname'], $db['username'], $db['password']);
$rows = $pdo->query("SELECT sku FROM catalog_product_entity WHERE sku LIKE 'TYO-%' LIMIT 15")->fetchAll(PDO::FETCH_COLUMN);
echo implode("\n", $rows) . "\n";
$cnt = $pdo->query("SELECT COUNT(*) FROM catalog_product_entity WHERE sku LIKE 'TYO-%'")->fetchColumn();
echo "uae_tyo_count=$cnt\n";
