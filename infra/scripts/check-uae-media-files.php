<?php
$env = include '/var/www/magento/app/etc/env.php';
$db = $env['db']['connection']['default'];
$pdo = new PDO('mysql:host='.$db['host'].';dbname='.$db['dbname'], $db['username'], $db['password']);
$files = $pdo->query("SELECT value FROM catalog_product_entity_media_gallery WHERE value LIKE '/catalog/product/%' LIMIT 10")->fetchAll(PDO::FETCH_COLUMN);
foreach ($files as $f) {
    $path = '/var/www/magento/pub/media' . $f;
    echo $f . ' => ' . (file_exists($path) ? 'exists' : 'MISSING') . "\n";
}
