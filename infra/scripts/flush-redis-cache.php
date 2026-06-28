<?php
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();

$cacheManager = $om->get('Magento\Framework\App\Cache\Manager');
$cacheManager->flush(['full_page', 'block_html', 'layout', 'translate', 'config']);

$env = include BP . '/app/etc/env.php';
$opts = $env['cache']['frontend']['page_cache']['backend_options'];
$redis = new Redis();
$redis->connect($opts['server'], (int)$opts['port'], 5);
$redis->select((int)$opts['database']);
$keysBefore = $redis->dbSize();
$redis->flushDB();
echo "Flushed Redis page_cache DB {$opts['database']}, had ~{$keysBefore} keys\n";

$opts2 = $env['cache']['frontend']['default']['backend_options'];
$redis2 = new Redis();
$redis2->connect($opts2['server'], (int)$opts2['port'], 5);
$redis2->select((int)$opts2['database']);
$keysBefore2 = $redis2->dbSize();
$redis2->flushDB();
echo "Flushed Redis default cache DB {$opts2['database']}, had ~{$keysBefore2} keys\n";

echo "Done\n";
