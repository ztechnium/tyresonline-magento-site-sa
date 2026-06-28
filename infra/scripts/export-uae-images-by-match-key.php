#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Export UAE product images indexed by match_key (brand + tyre size labels).
 * Run on UAE server:
 *   sudo -u www-data php export-uae-images-by-match-key.php > /tmp/uae-images-by-key.json
 */

$env = include '/var/www/magento/app/etc/env.php';
$db = $env['db']['connection']['default'];
$pdo = new PDO(
    'mysql:host=' . $db['host'] . ';dbname=' . $db['dbname'],
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

function attributeId(PDO $pdo, string $code): int
{
    static $cache = [];
    if (!isset($cache[$code])) {
        $stmt = $pdo->prepare(
            'SELECT attribute_id FROM eav_attribute WHERE attribute_code = ? AND entity_type_id = 4'
        );
        $stmt->execute([$code]);
        $cache[$code] = (int) $stmt->fetchColumn();
    }
    return $cache[$code];
}

function optionLabel(PDO $pdo, int $attributeId, $value): string
{
    if ($value === null || $value === '') {
        return '';
    }
    static $cache = [];
    $key = $attributeId . ':' . $value;
    if (!isset($cache[$key])) {
        $stmt = $pdo->prepare(
            'SELECT value FROM eav_attribute_option_value
             WHERE option_id = ? AND store_id = 0 LIMIT 1'
        );
        $stmt->execute([(int) $value]);
        $cache[$key] = (string) ($stmt->fetchColumn() ?: '');
    }
    return $cache[$key];
}

$tyreSizeId = attributeId($pdo, 'tyre_size');
$brandId = attributeId($pdo, 'mgs_brand');

$sql = '
SELECT cpe.sku, ts.value AS tyre_size, bi.value AS brand_int, bv.value AS brand_var,
       mg.value AS file, mg.media_type, mgv.label, mgv.position, mgv.disabled
FROM catalog_product_entity cpe
JOIN catalog_product_entity_media_gallery_value_to_entity mgve ON mgve.entity_id = cpe.entity_id
JOIN catalog_product_entity_media_gallery mg ON mg.value_id = mgve.value_id
JOIN catalog_product_entity_media_gallery_value mgv
  ON mgv.value_id = mg.value_id AND mgv.store_id = 0 AND mgv.disabled = 0
LEFT JOIN catalog_product_entity_int ts
  ON ts.entity_id = cpe.entity_id AND ts.attribute_id = ? AND ts.store_id = 0
LEFT JOIN catalog_product_entity_int bi
  ON bi.entity_id = cpe.entity_id AND bi.attribute_id = ? AND bi.store_id = 0
LEFT JOIN catalog_product_entity_varchar bv
  ON bv.entity_id = cpe.entity_id AND bv.attribute_id = ? AND bv.store_id = 0
ORDER BY cpe.sku, mgv.position
';

$stmt = $pdo->prepare($sql);
$stmt->execute([$tyreSizeId, $brandId, $brandId]);

$byKey = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $brandRaw = $row['brand_int'] ?: $row['brand_var'];
    $brand = optionLabel($pdo, $brandId, $brandRaw);
    $size = optionLabel($pdo, $tyreSizeId, $row['tyre_size']);
    $brandNorm = strtolower(trim(preg_replace('/\s+/', ' ', $brand)));
    $sizeNorm = strtolower(trim(preg_replace('/\s+/', ' ', $size)));
    $matchKey = $brandNorm . '|' . $sizeNorm;
    if ($matchKey === '|') {
        continue;
    }
    if (!isset($byKey[$matchKey])) {
        $byKey[$matchKey] = [
            'match_key' => $matchKey,
            'uae_sku' => $row['sku'],
            'brand' => $brand,
            'tyre_size' => $size,
            'images' => [],
        ];
    }
    $byKey[$matchKey]['images'][] = [
        'file' => $row['file'],
        'media_type' => $row['media_type'] ?: 'image',
        'label' => $row['label'] ?: '',
        'position' => (int) $row['position'],
    ];
}

fwrite(STDOUT, json_encode(array_values($byKey), JSON_UNESCAPED_UNICODE));
