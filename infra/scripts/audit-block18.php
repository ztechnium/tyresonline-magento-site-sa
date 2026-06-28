#!/usr/bin/env php
<?php
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$conn = $bootstrap->getObjectManager()->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();
$content = $conn->fetchOne('SELECT content FROM cms_block WHERE block_id = 18');
foreach (['الإمارات', 'الإمارات السبع', 'TyresOnline.ae'] as $needle) {
    $pos = 0;
    while (($pos = mb_strpos($content, $needle, $pos)) !== false) {
        $start = max(0, $pos - 60);
        echo "[$needle] ..." . mb_substr($content, $start, 140) . "...\n";
        $pos += mb_strlen($needle);
    }
}
