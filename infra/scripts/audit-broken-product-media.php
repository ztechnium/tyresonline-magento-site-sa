#!/usr/bin/env php
<?php
declare(strict_types=1);
/**
 * Report products whose image path is set in DB but media file is missing on disk.
 *
 * Usage: sudo -u www-data php audit-broken-product-media.php [--limit=20]
 */

$limit = 20;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--limit=')) {
        $limit = max(1, (int) substr($arg, 8));
    }
}

$mediaRoot = '/var/www/magento/pub/media';
$env = include '/var/www/magento/app/etc/env.php';
$db = $env['db']['connection']['default'];
$pdo = new PDO(
    'mysql:host=' . $db['host'] . ';dbname=' . $db['dbname'],
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

function mediaExists(string $mediaRoot, string $file): bool
{
    $file = ltrim($file, '/');
    if ($file === '' || $file === 'no_selection') {
        return false;
    }
    if (str_starts_with($file, 'catalog/product/')) {
        return is_file(rtrim($mediaRoot, '/') . '/' . $file);
    }
    return is_file(rtrim($mediaRoot, '/') . '/catalog/product/' . $file);
}

$imageAttrId = (int) $pdo->query(
    "SELECT attribute_id FROM eav_attribute WHERE attribute_code='image' AND entity_type_id=4"
)->fetchColumn();

$total = (int) $pdo->query('SELECT COUNT(*) FROM catalog_product_entity')->fetchColumn();
$noImage = (int) $pdo->query(
    "SELECT COUNT(*) FROM catalog_product_entity cpe
     LEFT JOIN catalog_product_entity_varchar img ON img.entity_id=cpe.entity_id
       AND img.attribute_id=$imageAttrId AND img.store_id=0
     WHERE img.value IS NULL OR img.value='' OR img.value='no_selection'"
)->fetchColumn();

$rows = $pdo->query(
    "SELECT cpe.sku, img.value AS image_path
     FROM catalog_product_entity cpe
     JOIN catalog_product_entity_varchar img ON img.entity_id=cpe.entity_id
       AND img.attribute_id=$imageAttrId AND img.store_id=0
     WHERE img.value IS NOT NULL AND img.value != '' AND img.value != 'no_selection'"
)->fetchAll(PDO::FETCH_ASSOC);

$broken = [];
foreach ($rows as $row) {
    if (!mediaExists($mediaRoot, (string) $row['image_path'])) {
        $broken[] = $row;
    }
}

echo "total_products=$total\n";
echo "without_image_attr=$noImage\n";
echo "with_image_attr=" . count($rows) . "\n";
echo "broken_media_files=" . count($broken) . "\n";
echo "sample_broken:\n";
foreach (array_slice($broken, 0, $limit) as $row) {
    echo "  {$row['sku']} path={$row['image_path']}\n";
}
