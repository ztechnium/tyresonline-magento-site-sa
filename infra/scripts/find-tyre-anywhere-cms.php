<?php
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$c = $bootstrap->getObjectManager()->get('Magento\Framework\App\ResourceConnection')->getConnection();

foreach (['tyre-anywhere', 'Map.svg', 'shop-tyre', 'home-page.phtml'] as $needle) {
    $rows = $c->fetchAll('SELECT identifier FROM cms_block WHERE content LIKE ?', ["%$needle%"]);
    echo "$needle in cms_block: " . count($rows) . PHP_EOL;
    foreach ($rows as $r) {
        echo '  ' . $r['identifier'] . PHP_EOL;
    }
}

$rows = $c->fetchAll('SELECT identifier FROM cms_page WHERE content LIKE ?', ['%tyre-anywhere%']);
echo 'tyre-anywhere in cms_page: ' . count($rows) . PHP_EOL;
foreach ($rows as $r) {
    echo '  ' . $r['identifier'] . PHP_EOL;
}
