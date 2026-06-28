#!/usr/bin/env php
<?php
require __DIR__ . '/app/bootstrap.php';
$bootstrap = Magento\Framework\Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$state = $om->get(\Magento\Framework\App\State::class);
try { $state->setAreaCode('adminhtml'); } catch (\Exception $e) {}
$conn = $om->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();

echo "=== ALL about-us / all-tyre-brands pages ===\n";
foreach ($conn->fetchAll("SELECT page_id, identifier, title, LENGTH(content) len FROM cms_page WHERE identifier IN ('about-us','all-tyre-brands','storelocator') OR title LIKE '%Arabic%' OR title LIKE '%About%'") as $r) {
    $uae = $conn->fetchOne("SELECT (content LIKE '%الإمارات%') FROM cms_page WHERE page_id=?", [$r['page_id']]);
    echo "{$r['page_id']} | {$r['identifier']} | uae=$uae | {$r['title']}\n";
}

echo "\n=== Block anywhere_in_the_uae (18) first 500 chars ===\n";
$c = $conn->fetchOne("SELECT content FROM cms_block WHERE block_id=18");
echo mb_substr(strip_tags($c), 0, 600) . "\n";

echo "\n=== about-us page 20 content first 800 chars ===\n";
$c = $conn->fetchOne("SELECT content FROM cms_page WHERE page_id=20");
if ($c) echo mb_substr(strip_tags($c), 0, 800) . "\n";

echo "\n=== about-us page 5 content first 800 chars ===\n";
$c = $conn->fetchOne("SELECT content FROM cms_page WHERE page_id=5");
if ($c) echo mb_substr(strip_tags($c), 0, 800) . "\n";

echo "\n=== all-tyre-brands pages ===\n";
foreach ($conn->fetchAll("SELECT page_id, identifier, title, LENGTH(content) len FROM cms_page WHERE identifier LIKE '%tyre-brand%' OR identifier LIKE '%all-tyre%'") as $r) {
    echo "{$r['page_id']} | {$r['identifier']} | {$r['title']}\n";
    $c = $conn->fetchOne("SELECT LEFT(content,400) FROM cms_page WHERE page_id=?", [$r['page_id']]);
    echo mb_substr(strip_tags($c), 0, 400) . "\n---\n";
}

echo "\n=== storelocator config paths ===\n";
foreach ($conn->fetchAll("SELECT path, LEFT(value,120) v FROM core_config_data WHERE path LIKE 'ecomteck_storelocator/%' AND (value LIKE '%UAE%' OR value LIKE '%Emirates%' OR value LIKE '%الإمارات%') LIMIT 20") as $r) {
    echo "{$r['path']}: {$r['v']}\n";
}

echo "\n=== New Tyres block 37 ===\n";
$c = $conn->fetchOne("SELECT content FROM cms_block WHERE block_id=37");
echo mb_substr(strip_tags($c), 0, 500) . "\n";
