#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Export KSA products with a stable match key (brand label + tyre size label).
 * Run on KSA server: sudo -u www-data php export-ksa-match-keys.php > /tmp/ksa-products-keys.json
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
SELECT cpe.entity_id, cpe.sku, ts.value AS tyre_size, bi.value AS brand_int, bv.value AS brand_var
FROM catalog_product_entity cpe
LEFT JOIN catalog_product_entity_int ts
  ON ts.entity_id = cpe.entity_id AND ts.attribute_id = ? AND ts.store_id = 0
LEFT JOIN catalog_product_entity_int bi
  ON bi.entity_id = cpe.entity_id AND bi.attribute_id = ? AND bi.store_id = 0
LEFT JOIN catalog_product_entity_varchar bv
  ON bv.entity_id = cpe.entity_id AND bv.attribute_id = ? AND bv.store_id = 0
';

$stmt = $pdo->prepare($sql);
$stmt->execute([$tyreSizeId, $brandId, $brandId]);

$out = [];
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
    $out[] = [
        'entity_id' => (int) $row['entity_id'],
        'sku' => $row['sku'],
        'match_key' => $matchKey,
        'brand' => $brand,
        'tyre_size' => $size,
    ];
}

fwrite(STDOUT, json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
