#!/usr/bin/env php
<?php
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$conn = $bootstrap->getObjectManager()->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();

echo "=== brand/list_page_settings config ===\n";
foreach ($conn->fetchAll("SELECT scope, scope_id, path, LEFT(value, 200) v FROM core_config_data WHERE path LIKE 'brand/list_page_settings/%' OR path LIKE 'mgs_brand/list_page_settings/%' ORDER BY scope, scope_id, path") as $r) {
    echo "{$r['scope']} {$r['scope_id']} {$r['path']}\n  {$r['v']}\n";
}

echo "\n=== cms_page all-tyre-brands ===\n";
foreach ($conn->fetchAll("SELECT page_id, title, LENGTH(content) len FROM cms_page WHERE identifier = 'all-tyre-brands'") as $r) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
}

echo "\n=== template check ===\n";
$paths = [
    BP . '/app/design/frontend/Hditsol/tyresonline/MGS_Brand/templates/brands.phtml',
    BP . '/app/code/MGS/Brand/view/frontend/templates/brands.phtml',
];
foreach ($paths as $p) {
    echo $p . ': ' . (is_file($p) ? (strpos(file_get_contents($p), 'content-block') !== false ? 'theme custom' : 'exists') : 'missing') . "\n";
}
