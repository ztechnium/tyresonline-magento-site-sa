#!/usr/bin/env php
<?php
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$conn = $bootstrap->getObjectManager()->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();

echo "=== home_main_banner_tyres blocks ===\n";
foreach ($conn->fetchAll("SELECT block_id, identifier, title FROM cms_block WHERE identifier LIKE '%home_main%' OR identifier LIKE '%banner%tyre%'") as $r) {
    $c = $conn->fetchOne('SELECT content FROM cms_block WHERE block_id = ?', [$r['block_id']]);
    $uae = substr_count($c, 'الإمارات') + substr_count(strtolower($c), 'uae');
    echo "block {$r['block_id']} ({$r['identifier']}) uae_hits={$uae} len=" . strlen($c) . "\n";
    if ($uae) echo mb_substr(strip_tags($c), 0, 500) . "\n---\n";
}

echo "\n=== Mageplaza banners name_ar/title_ar with UAE ===\n";
foreach ($conn->fetchAll("SELECT banner_id, name_ar, title_ar FROM mageplaza_bannerslider_banner WHERE name_ar LIKE '%الإمارات%' OR title_ar LIKE '%الإمارات%' OR content_ar LIKE '%الإمارات%' LIMIT 10") as $r) {
    echo "banner {$r['banner_id']}: " . ($r['title_ar'] ?: $r['name_ar']) . "\n";
}

echo "\n=== Store design config ===\n";
foreach ($conn->fetchAll("SELECT scope, scope_id, path, value FROM core_config_data WHERE path IN ('design/header/logo_src','design/footer/copyright','design/head/default_title')") as $r) {
    echo "{$r['scope']} {$r['scope_id']} {$r['path']} = {$r['value']}\n";
}
