<?php
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../../app/bootstrap.php';
$c = Bootstrap::create(BP, $_SERVER)->getObjectManager()->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();
foreach ([27, 28] as $id) {
    file_put_contents("/tmp/privacy-{$id}.html", $c->fetchOne('SELECT content FROM cms_page WHERE page_id = ?', [$id]));
    echo "page {$id} len=" . strlen(file_get_contents("/tmp/privacy-{$id}.html")) . "\n";
}
