<?php
$env = include '/var/www/magento/app/etc/env.php';
$db = $env['db']['connection']['default'];
$pdo = new PDO('mysql:host='.$db['host'].';dbname='.$db['dbname'], $db['username'], $db['password']);
foreach ($pdo->query('DESCRIBE catalog_product_entity_media_gallery') as $r) {
    echo implode("\t", $r) . "\n";
}
$aid = $pdo->query("SELECT attribute_id FROM eav_attribute WHERE attribute_code='media_gallery' AND entity_type_id=4")->fetchColumn();
echo "media_gallery_attribute_id=$aid\n";
