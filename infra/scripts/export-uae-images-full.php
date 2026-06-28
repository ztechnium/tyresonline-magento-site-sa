#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Export UAE images indexed by multiple match keys.
 * Run on UAE: sudo -u www-data php export-uae-images-full.php > /tmp/uae-images-full.json
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
            'SELECT value FROM eav_attribute_option_value WHERE option_id = ? AND store_id = 0 LIMIT 1'
        );
        $stmt->execute([(int) $value]);
        $cache[$key] = (string) ($stmt->fetchColumn() ?: '');
    }
    return $cache[$key];
}

function norm(string $s): string
{
    return strtolower(trim(preg_replace('/\s+/', ' ', $s)));
}

function normSize(string $s): string
{
    $s = norm($s);
    $s = preg_replace('/\b(zr|zr\s*)\b/i', 'r', $s);
    $s = preg_replace('/[^a-z0-9]/', '', $s);
    return $s;
}

$tyreSizeId = attributeId($pdo, 'tyre_size');
$brandId = attributeId($pdo, 'mgs_brand');
$widthId = attributeId($pdo, 'width');
$heightId = attributeId($pdo, 'height');
$rimId = attributeId($pdo, 'rim');
$patternId = attributeId($pdo, 'pattern');

$sql = '
SELECT cpe.sku, cpe.entity_id,
       ts.value AS tyre_size, bi.value AS brand_int, bv.value AS brand_var,
       w.value AS width, h.value AS height, r.value AS rim, p.value AS pattern,
       mg.value AS file, mg.media_type, mgv.label, mgv.position
FROM catalog_product_entity cpe
JOIN catalog_product_entity_media_gallery_value_to_entity mgve ON mgve.entity_id = cpe.entity_id
JOIN catalog_product_entity_media_gallery mg ON mg.value_id = mgve.value_id
JOIN catalog_product_entity_media_gallery_value mgv
  ON mgv.value_id = mg.value_id AND mgv.store_id = 0 AND mgv.disabled = 0
LEFT JOIN catalog_product_entity_int ts ON ts.entity_id = cpe.entity_id AND ts.attribute_id = ? AND ts.store_id = 0
LEFT JOIN catalog_product_entity_int bi ON bi.entity_id = cpe.entity_id AND bi.attribute_id = ? AND bi.store_id = 0
LEFT JOIN catalog_product_entity_varchar bv ON bv.entity_id = cpe.entity_id AND bv.attribute_id = ? AND bv.store_id = 0
LEFT JOIN catalog_product_entity_int w ON w.entity_id = cpe.entity_id AND w.attribute_id = ? AND w.store_id = 0
LEFT JOIN catalog_product_entity_int h ON h.entity_id = cpe.entity_id AND h.attribute_id = ? AND h.store_id = 0
LEFT JOIN catalog_product_entity_int r ON r.entity_id = cpe.entity_id AND r.attribute_id = ? AND r.store_id = 0
LEFT JOIN catalog_product_entity_int p ON p.entity_id = cpe.entity_id AND p.attribute_id = ? AND p.store_id = 0
ORDER BY cpe.sku, mgv.position
';

$stmt = $pdo->prepare($sql);
$stmt->execute([$tyreSizeId, $brandId, $brandId, $widthId, $heightId, $rimId, $patternId]);

$byKey = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $brandRaw = $row['brand_int'] ?: $row['brand_var'];
    $brand = optionLabel($pdo, $brandId, $brandRaw);
    $size = optionLabel($pdo, $tyreSizeId, $row['tyre_size']);
    $width = optionLabel($pdo, $widthId, $row['width']);
    $height = optionLabel($pdo, $heightId, $row['height']);
    $rim = optionLabel($pdo, $rimId, $row['rim']);
    $pattern = optionLabel($pdo, $patternId, $row['pattern']);

    $brandNorm = norm($brand);
    $sizeNorm = norm($size);
    $keys = [];
    if ($brandNorm && $sizeNorm) {
        $keys[] = ['type' => 'brand_size', 'key' => $brandNorm . '|' . $sizeNorm];
        $keys[] = ['type' => 'brand_size_compact', 'key' => $brandNorm . '|' . normSize($size)];
    }
    if ($brandNorm && $width && $height && $rim) {
        $keys[] = ['type' => 'brand_whr', 'key' => $brandNorm . '|' . norm($width) . '|' . norm($height) . '|' . norm($rim)];
        $keys[] = ['type' => 'brand_whr_compact', 'key' => $brandNorm . '|' . normSize("$width $height r$rim")];
    }
    if ($brandNorm && $pattern) {
        $keys[] = ['type' => 'brand_pattern', 'key' => $brandNorm . '|' . norm($pattern)];
    }

    $img = [
        'file' => $row['file'],
        'media_type' => $row['media_type'] ?: 'image',
        'label' => $row['label'] ?: '',
        'position' => (int) $row['position'],
    ];

    foreach ($keys as $k) {
        $composite = $k['type'] . '::' . $k['key'];
        if (!isset($byKey[$composite])) {
            $byKey[$composite] = [
                'match_type' => $k['type'],
                'match_key' => $k['key'],
                'uae_sku' => $row['sku'],
                'brand' => $brand,
                'images' => [],
            ];
        }
        $byKey[$composite]['images'][] = $img;
    }
}

fwrite(STDOUT, json_encode(array_values($byKey), JSON_UNESCAPED_UNICODE));
