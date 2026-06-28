<?php
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$c = $om->get('Magento\Framework\App\ResourceConnection')->getConnection();

foreach (['cms_page' => ['identifier', 'content'], 'cms_block' => ['identifier', 'content']] as $table => $cols) {
    $rows = $c->fetchAll(
        'SELECT ' . implode(',', $cols) . ' FROM ' . $table . ' WHERE content LIKE ?',
        ['%Map.svg%']
    );
    echo "$table: " . count($rows) . PHP_EOL;
    foreach ($rows as $row) {
        echo '  ' . $row['identifier'] . PHP_EOL;
    }
}

// Which template is used for homepage
$page = $c->fetchRow("SELECT page_id, identifier, content FROM cms_page WHERE identifier IN ('home','home-page') OR page_id=1");
if ($page) {
    echo 'Home CMS page: ' . $page['identifier'] . ' len=' . strlen($page['content']) . PHP_EOL;
    echo $page['content'] . PHP_EOL;
}

$layouts = array_merge(
    glob(BP . '/app/design/frontend/Hditsol/*/Magento_Theme/layout/*.xml') ?: [],
    glob(BP . '/app/design/frontend/Hditsol/*/Magento_Cms/layout/*.xml') ?: []
);
foreach ($layouts as $f) {
    $xml = file_get_contents($f);
    if (stripos($xml, 'home-page') !== false) {
        echo "Layout ref: $f\n$xml\n---\n";
    }
}
