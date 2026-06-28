#!/usr/bin/env php
<?php
declare(strict_types=1);
require '/var/www/magento/app/bootstrap.php';
$om = Magento\Framework\App\Bootstrap::create(BP, $_SERVER)->getObjectManager();
$c = $om->get(Magento\Framework\App\ResourceConnection::class)->getConnection();

echo "=== store theme ===\n";
foreach ($c->fetchAll("SELECT s.store_id, s.code, t.theme_path FROM store s JOIN core_config_data cfg ON cfg.path='design/theme/theme_id' AND cfg.scope='stores' AND cfg.scope_id=s.store_id JOIN theme t ON t.theme_id=cfg.value WHERE s.store_id IN (1,2)") as $r) {
    echo json_encode($r) . "\n";
}

echo "=== cms home pages ===\n";
foreach ($c->fetchAll("SELECT page_id, identifier, title, is_active, LEFT(content,120) snippet FROM cms_page WHERE identifier IN ('home','no-route') OR title LIKE '%home%' ORDER BY page_id") as $r) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
}

echo "=== cms home store assignment ===\n";
foreach ($c->fetchAll("SELECT cp.page_id, cp.identifier, cps.store_id FROM cms_page cp JOIN cms_page_store cps ON cp.page_id=cps.page_id WHERE cp.identifier='home'") as $r) {
    echo json_encode($r) . "\n";
}

echo "=== default home config ===\n";
foreach ($c->fetchAll("SELECT scope, scope_id, path, value FROM core_config_data WHERE path IN ('web/default/cms_home_page','design/theme/theme_id','web/default/front')") as $r) {
    echo json_encode($r) . "\n";
}
