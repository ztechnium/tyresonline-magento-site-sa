<?php
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../../app/bootstrap.php';
$c = Bootstrap::create(BP, $_SERVER)->getObjectManager()->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();
foreach ([25, 26] as $id) {
    file_put_contents("/tmp/terms-{$id}.html", $c->fetchOne('SELECT content FROM cms_page WHERE page_id = ?', [$id]));
    echo "page {$id} len=" . strlen(file_get_contents("/tmp/terms-{$id}.html")) . "\n";
}
