<?php
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../../app/bootstrap.php';
$c = Bootstrap::create(BP, $_SERVER)->getObjectManager()->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();

foreach ($c->fetchAll("SELECT page_id, identifier, title, meta_title, LENGTH(content) len FROM cms_page WHERE identifier LIKE '%terms%' OR title LIKE '%Terms%' OR title LIKE '%conditions%' OR title LIKE '%شروط%'") as $r) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
    $stores = $c->fetchCol('SELECT store_id FROM cms_page_store WHERE page_id = ?', [$r['page_id']]);
    echo '  stores: ' . implode(',', $stores) . "\n";
    $preview = $c->fetchOne('SELECT LEFT(content, 250) FROM cms_page WHERE page_id = ?', [$r['page_id']]);
    echo '  preview: ' . mb_substr(strip_tags(html_entity_decode($preview)), 0, 180) . "\n\n";
}
