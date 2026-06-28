#!/usr/bin/env php
<?php
$uaeEnv = include '/var/www/magento/app/etc/env.php';
$u = $uaeEnv['db']['connection']['default'];
$uae = new PDO('mysql:host='.$u['host'].';dbname='.$u['dbname'], $u['username'], $u['password']);

$ksaHost = getenv('KSA_DB_HOST') ?: 'tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com';
$ksaDb = getenv('KSA_DB_NAME') ?: 'tyresonline_sa';
$ksaUser = getenv('KSA_DB_USER') ?: 'magento';
$ksaPass = getenv('KSA_DB_PASS') ?: 'uxaYMIQRwEU0AFl1Ck69LJnH';
$ksa = new PDO("mysql:host=$ksaHost;dbname=$ksaDb", $ksaUser, $ksaPass);

$ksaSkus = $ksa->query('SELECT entity_id, sku FROM catalog_product_entity')->fetchAll(PDO::FETCH_KEY_PAIR);
echo 'ksa_products=' . count($ksaSkus) . "\n";

$stmt = $uae->query("
SELECT cpe.sku, COUNT(mg.value_id) AS imgs
FROM catalog_product_entity cpe
JOIN catalog_product_entity_media_gallery_value_to_entity mgve ON mgve.entity_id = cpe.entity_id
JOIN catalog_product_entity_media_gallery mg ON mg.value_id = mgve.value_id
GROUP BY cpe.sku
");
$uaeBySku = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $uaeBySku[$row['sku']] = (int)$row['imgs'];
}
echo 'uae_products_with_images=' . count($uaeBySku) . "\n";

$match = 0;
$noUae = 0;
foreach ($ksaSkus as $id => $sku) {
    if (isset($uaeBySku[$sku])) {
        $match++;
    } else {
        $noUae++;
    }
}
echo "ksa_skus_with_uae_images=$match\n";
echo "ksa_skus_without_uae_match=$noUae\n";

$sample = array_slice(array_keys(array_filter($ksaSkus, fn($sku) => !isset($uaeBySku[$sku]))), 0, 10);
echo 'sample_unmatched: ' . implode(', ', $sample) . "\n";
