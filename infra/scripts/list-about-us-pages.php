<?php
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$c = $bootstrap->getObjectManager()->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();

echo "=== cms_page about-us ===\n";
foreach ($c->fetchAll("SELECT page_id, identifier, title, meta_title, LEFT(content,120) preview FROM cms_page WHERE identifier = 'about-us'") as $r) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
    $stores = $c->fetchCol("SELECT store_id FROM cms_page_store WHERE page_id = ?", [$r['page_id']]);
    echo "  stores: " . implode(',', $stores) . "\n";
}
