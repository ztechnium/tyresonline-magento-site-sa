#!/usr/bin/env php
<?php
declare(strict_types=1);
require '/var/www/magento/app/bootstrap.php';
$c = Magento\Framework\App\Bootstrap::create(BP, $_SERVER)->getObjectManager()
    ->get(Magento\Framework\App\ResourceConnection::class)->getConnection();

foreach ($c->fetchAll("SELECT scope, scope_id, path, value FROM core_config_data WHERE path LIKE 'web/default/%' ORDER BY path, scope, scope_id") as $r) {
    echo json_encode($r) . "\n";
}

echo "=== brands with empty url_key ===\n";
foreach ($c->fetchAll("SELECT brand_id, name, url_key, status FROM mgs_brand WHERE url_key IS NULL OR url_key = ''") as $r) {
    echo json_encode($r) . "\n";
}
