<?php
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$c = $bootstrap->getObjectManager()->get('Magento\Framework\App\ResourceConnection')->getConnection();

$rows = $c->fetchAll("SELECT store_id, LEFT(content, 300) AS snippet, LENGTH(content) AS len FROM cms_page_store WHERE page_id = (SELECT page_id FROM cms_page WHERE identifier='home')");
foreach ($rows as $r) {
    echo "store {$r['store_id']} len={$r['len']}\n{$r['snippet']}\n---\n";
}

// Check if full page cache table exists
$tables = $c->fetchCol("SHOW TABLES LIKE '%cache%'");
echo "Cache tables: " . implode(', ', $tables) . "\n";

// env cache config
$env = include BP . '/app/etc/env.php';
echo 'cache frontend: ' . json_encode($env['cache']['frontend']['default']['backend_options'] ?? 'file') . "\n";
echo 'full_page: ' . json_encode($env['cache']['frontend']['page_cache']['backend_options'] ?? 'default') . "\n";
