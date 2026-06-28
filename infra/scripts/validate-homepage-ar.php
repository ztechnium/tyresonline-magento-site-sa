#!/usr/bin/env php
<?php
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$conn = $bootstrap->getObjectManager()->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();

echo "=== CMS blocks with الإمارات on homepage-related identifiers ===\n";
$rows = $conn->fetchAll(
    "SELECT block_id, identifier, title,
     (LENGTH(content)-LENGTH(REPLACE(content,'الإمارات','')))/CHAR_LENGTH('الإمارات') AS uae
     FROM cms_block
     WHERE identifier LIKE '%home%' OR identifier LIKE '%uae%' OR identifier LIKE '%anywhere%' OR identifier LIKE '%offer%'
     HAVING uae > 0 ORDER BY uae DESC"
);
foreach ($rows as $r) {
    echo "block {$r['block_id']} ({$r['identifier']}) uae={$r['uae']}\n";
    $content = $conn->fetchOne('SELECT content FROM cms_block WHERE block_id = ?', [$r['block_id']]);
    $pos = mb_strpos($content, 'الإمارات');
    if ($pos !== false) echo '  ...' . mb_substr($content, max(0, $pos - 40), 120) . "...\n";
}

echo "\n=== Block 18 full UAE scan ===\n";
$b18 = $conn->fetchOne('SELECT content FROM cms_block WHERE block_id = 18');
echo 'uae_count=' . substr_count($b18, 'الإمارات') . "\n";
