#!/usr/bin/env php
<?php
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../../app/bootstrap.php';
$conn = Bootstrap::create(BP, $_SERVER)->getObjectManager()
    ->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();

$rows = $conn->fetchAll(
    'SELECT stores_id, name, name_rtl, address, address_rtl, opening_hours_text1, opening_hours_text1_rtl, status
     FROM ecomteck_storelocator_stores WHERE status = 1 ORDER BY position LIMIT 10'
);
echo 'count=' . count($rows) . "\n";
foreach ($rows as $r) {
    echo "{$r['stores_id']}\n";
    echo "  name: [{$r['name']}]\n";
    echo "  name_rtl: [{$r['name_rtl']}]\n";
    echo "  address: [{$r['address']}]\n";
    echo "  address_rtl: [{$r['address_rtl']}]\n";
    echo "  hours1_rtl: [{$r['opening_hours_text1_rtl']}]\n";
}

$emptyRtl = $conn->fetchOne(
    'SELECT COUNT(*) FROM ecomteck_storelocator_stores WHERE status = 1 AND (name_rtl IS NULL OR name_rtl = "")'
);
$total = $conn->fetchOne('SELECT COUNT(*) FROM ecomteck_storelocator_stores WHERE status = 1');
echo "empty name_rtl: $emptyRtl / $total\n";
