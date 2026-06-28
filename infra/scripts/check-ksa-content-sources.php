<?php
require '/var/www/magento/app/bootstrap.php';
$bootstrap = Magento\Framework\Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$state = $om->get(Magento\Framework\App\State::class);
try { $state->setAreaCode('adminhtml'); } catch (Exception $e) {}
$conn = $om->get(Magento\Framework\App\ResourceConnection::class)->getConnection();

echo "=== AR store (scope_id=2) config ===\n";
$paths = [
    'general/store_information/name',
    'design/head/default_title',
    'design/head/default_description',
    'design/footer/copyright',
    'general/store_information/country_id',
];
foreach ($paths as $path) {
    $v = $conn->fetchOne(
        "SELECT value FROM core_config_data WHERE path = ? AND scope = 'stores' AND scope_id = 2",
        [$path]
    );
    echo "$path: " . ($v ?: '(empty)') . "\n";
}

echo "\n=== CMS home page ===\n";
$page = $conn->fetchRow("SELECT page_id, title, meta_title, meta_description FROM cms_page WHERE identifier = 'home' LIMIT 1");
print_r($page);

$pageId = (int)($page['page_id'] ?? 0);
$content = $conn->fetchOne(
    "SELECT content FROM cms_page_store WHERE page_id = ? AND store_id IN (0,2) ORDER BY store_id DESC LIMIT 1",
    [$pageId]
);
$uaeCount = substr_count((string)$content, 'الإمارات');
$ksaCount = substr_count((string)$content, 'المملكة');
echo "UAE mentions in home CMS HTML: $uaeCount\n";
echo "KSA mentions in home CMS HTML: $ksaCount\n";

echo "\n=== CMS blocks with UAE text (sample) ===\n";
$blocks = $conn->fetchAll(
    "SELECT b.block_id, b.identifier, b.title
     FROM cms_block b
     JOIN cms_block_store s ON s.block_id = b.block_id
     WHERE s.store_id IN (0,2) AND b.content LIKE '%الإمارات%'
     LIMIT 15"
);
foreach ($blocks as $b) {
    echo "{$b['identifier']} | {$b['title']}\n";
}

echo "\n=== Store views ===\n";
$stores = $conn->fetchAll('SELECT store_id, code, name FROM store');
foreach ($stores as $s) {
    echo "store {$s['store_id']}: {$s['code']} | {$s['name']}\n";
}
