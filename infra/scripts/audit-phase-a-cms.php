#!/usr/bin/env php
<?php
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$state = $om->get(\Magento\Framework\App\State::class);
try { $state->setAreaCode('adminhtml'); } catch (\Exception $e) {}

$conn = $om->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();

$identifiers = [
    'home', 'about-us', 'storelocator', 'tyres-brand', 'brands', 'all-brands',
    'mobile-tyre-fitting-service-in-uae', 'fitting-locations',
];

echo "=== CMS PAGES ===\n";
foreach ($identifiers as $id) {
    $row = $conn->fetchRow("SELECT page_id, identifier, title, LENGTH(content) len FROM cms_page WHERE identifier = ?", [$id]);
    if ($row) {
        $uae = $conn->fetchOne("SELECT COUNT(*) FROM cms_page WHERE page_id = ? AND content LIKE '%الإمارات%'", [$row['page_id']]);
        echo "{$row['identifier']} | id={$row['page_id']} | len={$row['len']} | uae={$uae} | {$row['title']}\n";
    }
}

echo "\n=== CMS BLOCKS (sample) ===\n";
$blocks = $conn->fetchAll(
    "SELECT block_id, identifier, title, LENGTH(content) len
     FROM cms_block
     WHERE identifier LIKE '%uae%' OR identifier LIKE '%home%' OR identifier LIKE '%brand%'
        OR identifier LIKE '%fitting%' OR identifier LIKE '%about%' OR identifier LIKE '%anywhere%'
     ORDER BY identifier LIMIT 40"
);
foreach ($blocks as $b) {
    echo "{$b['identifier']} | id={$b['block_id']} | len={$b['len']} | {$b['title']}\n";
}

echo "\n=== Home pages (default + AR) ===\n";
foreach ($conn->fetchAll("SELECT page_id, identifier, title, LENGTH(content) len FROM cms_page WHERE identifier = 'home' OR page_id IN (2, 42) ORDER BY page_id") as $row) {
    $uae = $conn->fetchOne("SELECT (LENGTH(content)-LENGTH(REPLACE(content,'الإمارات','')))/CHAR_LENGTH('الإمارات') FROM cms_page WHERE page_id = ?", [$row['page_id']]);
    echo "id={$row['page_id']} | len={$row['len']} | uae={$uae} | {$row['title']}\n";
}
