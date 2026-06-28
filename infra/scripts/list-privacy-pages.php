<?php
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$c = $bootstrap->getObjectManager()->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();

foreach ($c->fetchAll("SELECT page_id, identifier, title, meta_title, LENGTH(content) len FROM cms_page WHERE identifier LIKE '%privacy%' OR title LIKE '%Privacy%' OR title LIKE '%خصوص%'") as $r) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
    $stores = $c->fetchCol('SELECT store_id FROM cms_page_store WHERE page_id = ?', [$r['page_id']]);
    echo '  stores: ' . implode(',', $stores) . "\n";
    $preview = $c->fetchOne('SELECT LEFT(content, 300) FROM cms_page WHERE page_id = ?', [$r['page_id']]);
    echo '  preview: ' . mb_substr(strip_tags($preview), 0, 200) . "\n\n";
}
