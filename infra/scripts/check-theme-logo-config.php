#!/usr/bin/env php
<?php
require '/var/www/magento/app/bootstrap.php';
$c = Magento\Framework\App\Bootstrap::create(BP, $_SERVER)->getObjectManager()
    ->get(Magento\Framework\App\ResourceConnection::class)->getConnection();

echo "=== theme + logo config ===\n";
foreach ($c->fetchAll(
    "SELECT scope, scope_id, path, value FROM core_config_data
     WHERE path LIKE '%logo%' OR path LIKE 'design/theme%' OR path LIKE 'design/header%'
     ORDER BY path, scope, scope_id"
) as $r) {
    echo json_encode($r) . "\n";
}

echo "=== store themes ===\n";
foreach ($c->fetchAll(
    "SELECT s.store_id, s.code, t.theme_path FROM store s
     JOIN core_config_data cfg ON cfg.path='design/theme/theme_id' AND cfg.scope='stores' AND cfg.scope_id=s.store_id
     JOIN theme t ON t.theme_id=cfg.value"
) as $r) {
    echo json_encode($r) . "\n";
}
