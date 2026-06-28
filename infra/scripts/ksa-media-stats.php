<?php
$env = include '/var/www/magento/app/etc/env.php';
$db = $env['db']['connection']['default'];
$pdo = new PDO('mysql:host='.$db['host'].';dbname='.$db['dbname'], $db['username'], $db['password']);
echo 'gallery_rows=' . $pdo->query('SELECT COUNT(*) FROM catalog_product_entity_media_gallery')->fetchColumn() . "\n";
echo 'linked_products=' . $pdo->query('SELECT COUNT(DISTINCT entity_id) FROM catalog_product_entity_media_gallery_value_to_entity')->fetchColumn() . "\n";
