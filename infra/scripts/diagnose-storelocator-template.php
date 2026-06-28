#!/usr/bin/env php
<?php
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../../app/bootstrap.php';
$conn = Bootstrap::create(BP, $_SERVER)->getObjectManager()
    ->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();

$rows = $conn->fetchAll(
    "SELECT config_id, scope, scope_id, LENGTH(value) AS len
     FROM core_config_data
     WHERE path = ?
     ORDER BY scope, scope_id",
    ['ecomteck_storelocator/template/location_list_description']
);
print_r($rows);

foreach ([436, 867, 868] as $id) {
    $v = (string)$conn->fetchOne('SELECT value FROM core_config_data WHERE config_id = ?', [$id]);
    echo "config_id=$id len=" . strlen($v) . "\n";
    echo '  english See On Map: ' . (strpos($v, 'See On Map') !== false ? 'yes' : 'no') . "\n";
    echo '  box chars: ' . (preg_match('/[\x{2500}-\x{25FF}]/u', $v) ? 'yes' : 'no') . "\n";
    echo '  arabic block: ' . (preg_match('/[\x{0600}-\x{06FF}]/u', $v) ? 'yes' : 'no') . "\n";
    if (preg_match('/google-map-direction.*?<span>([^<]+)<\\/span>/us', $v, $m)) {
        echo '  map span: ' . $m[1] . "\n";
        echo '  hex: ' . bin2hex($m[1]) . "\n";
    }
    if (preg_match('/checkboxInstaller.*?<label[^>]*>([^<]+)<\\/label>/us', $v, $m)) {
        echo '  select label: ' . trim($m[1]) . "\n";
        echo '  hex: ' . bin2hex(trim($m[1])) . "\n";
    }
    echo "\n";
}
