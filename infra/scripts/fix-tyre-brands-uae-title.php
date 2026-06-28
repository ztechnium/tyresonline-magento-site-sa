#!/usr/bin/env php
<?php
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../../app/bootstrap.php';
$conn = Bootstrap::create(BP, $_SERVER)->getObjectManager()->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();

$rows = $conn->fetchAll("SELECT config_id, scope, scope_id, path, LEFT(value, 150) v FROM core_config_data WHERE path LIKE '%list_page_settings/title%' OR path LIKE '%list_page_settings/description%'");
foreach ($rows as $r) {
    if (stripos($r['v'], 'UAE') !== false || stripos($r['v'], 'TyresOnline.ae') !== false || stripos($r['v'], 'Tires in UAE') !== false) {
        echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
    }
}

$newTitle = 'TYRE BRANDS WE TRUST | Best Tyres in KSA | TyresOnline.sa';
foreach ($conn->fetchAll("SELECT config_id, scope, scope_id, path, value FROM core_config_data WHERE path LIKE '%list_page_settings/title%'") as $r) {
    if (stripos($r['value'], 'UAE') !== false || stripos($r['value'], 'TyresOnline.ae') !== false) {
        $conn->update('core_config_data', ['value' => $newTitle], ['config_id = ?' => $r['config_id']]);
        echo "FIXED {$r['scope']} {$r['scope_id']} {$r['path']}\n";
    }
}
