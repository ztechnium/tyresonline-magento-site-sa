#!/usr/bin/env php
<?php
$host = 'tyresonline-ae-stg-rds.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com';
$user = getenv('UAE_DB_USER') ?: 'magento';
$pass = getenv('UAE_DB_PASS') ?: '';
if ($pass === '') {
    $env = include '/var/www/magento/app/etc/env.php';
    // Try reading from a one-line file if UAE creds stored separately
    fwrite(STDERR, "Set UAE_DB_PASS env var\n");
    exit(1);
}
try {
    $pdo = new PDO("mysql:host=$host;dbname=tyresonline_ae", $user, $pass, [PDO::ATTR_TIMEOUT => 5]);
    echo "connected\n";
    echo $pdo->query('SELECT COUNT(*) FROM catalog_product_entity_media_gallery')->fetchColumn() . "\n";
} catch (Throwable $e) {
    echo 'error: ' . $e->getMessage() . "\n";
}
