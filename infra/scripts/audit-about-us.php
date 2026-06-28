<?php
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$c = $bootstrap->getObjectManager()->get('Magento\Framework\App\ResourceConnection')->getConnection();

foreach ($c->fetchAll("SELECT page_id, identifier, title FROM cms_page WHERE identifier = 'about-us' OR title LIKE '%About%' OR content LIKE '%TYRESONLINE.AE%' OR content LIKE '%من هي TyresOnline%'") as $r) {
    echo "page {$r['page_id']} id={$r['identifier']} title={$r['title']}\n";
}

$pageId = $c->fetchOne("SELECT page_id FROM cms_page WHERE identifier = 'about-us' ORDER BY page_id DESC LIMIT 1");
$content = $c->fetchOne("SELECT content FROM cms_page WHERE page_id = ?", [$pageId]);
echo "\n=== page_id=$pageId len=" . strlen($content) . " ===\n";
echo $content;
