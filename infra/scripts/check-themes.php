#!/usr/bin/env php
<?php
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$conn = $om->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();
foreach ($conn->fetchAll('SELECT theme_id, theme_path, theme_title FROM theme') as $r) {
    echo "{$r['theme_id']} | {$r['theme_path']} | {$r['theme_title']}\n";
}
echo "\n=== mgs_brand config ===\n";
foreach ($conn->fetchAll("SELECT scope, scope_id, path, value FROM core_config_data WHERE path LIKE 'mgs_brand/%' OR path LIKE 'brand/%'") as $r) {
    echo "{$r['scope']} {$r['scope_id']} {$r['path']} = {$r['value']}\n";
}
