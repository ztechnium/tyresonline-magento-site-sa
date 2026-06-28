#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Export KSA products with multiple match keys and image status.
 * Run: sudo -u www-data php export-ksa-products-full.php > /tmp/ksa-products-full.json
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

$attrs = [
    'tyre_size' => attributeId($pdo, 'tyre_size'),
    'mgs_brand' => attributeId($pdo, 'mgs_brand'),
    'width' => attributeId($pdo, 'width'),
    'height' => attributeId($pdo, 'height'),
    'rim' => attributeId($pdo, 'rim'),
    'pattern' => attributeId($pdo, 'pattern'),
    'name' => attributeId($pdo, 'name'),
    'image' => attributeId($pdo, 'image'),
];

$sql = 'SELECT cpe.entity_id, cpe.sku FROM catalog_product_entity cpe';
$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

function loadAttr(PDO $pdo, int $entityId, int $attrId, string $type): ?string
{
    static $cache = [];
    $key = "$type:$entityId:$attrId";
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    $table = $type === 'int' ? 'catalog_product_entity_int' : 'catalog_product_entity_varchar';
    $stmt = $pdo->prepare("SELECT value FROM $table WHERE entity_id=? AND attribute_id=? AND store_id=0 LIMIT 1");
    $stmt->execute([$entityId, $attrId]);
    $val = $stmt->fetchColumn();
    $cache[$key] = $val === false ? null : (string) $val;
    return $cache[$key];
}

$out = [];
foreach ($rows as $row) {
    $eid = (int) $row['entity_id'];
    $brandRaw = loadAttr($pdo, $eid, $attrs['mgs_brand'], 'int')
        ?: loadAttr($pdo, $eid, $attrs['mgs_brand'], 'varchar');
    $brand = optionLabel($pdo, $attrs['mgs_brand'], $brandRaw);
    $size = optionLabel($pdo, $attrs['tyre_size'], loadAttr($pdo, $eid, $attrs['tyre_size'], 'int'));
    $width = optionLabel($pdo, $attrs['width'], loadAttr($pdo, $eid, $attrs['width'], 'int'));
    $height = optionLabel($pdo, $attrs['height'], loadAttr($pdo, $eid, $attrs['height'], 'int'));
    $rim = optionLabel($pdo, $attrs['rim'], loadAttr($pdo, $eid, $attrs['rim'], 'int'));
    $pattern = optionLabel($pdo, $attrs['pattern'], loadAttr($pdo, $eid, $attrs['pattern'], 'int'));
    $name = loadAttr($pdo, $eid, $attrs['name'], 'varchar') ?? '';
    $image = loadAttr($pdo, $eid, $attrs['image'], 'varchar') ?? '';
    $hasImage = $image !== '' && $image !== 'no_selection';

    $brandNorm = norm($brand);
    $sizeNorm = norm($size);
    $keys = [];
    if ($brandNorm !== '' && $sizeNorm !== '') {
        $keys['brand_size'] = $brandNorm . '|' . $sizeNorm;
        $keys['brand_size_compact'] = $brandNorm . '|' . normSize($size);
    }
    if ($brandNorm !== '' && $width !== '' && $height !== '' && $rim !== '') {
        $keys['brand_whr'] = $brandNorm . '|' . norm($width) . '|' . norm($height) . '|' . norm($rim);
        $keys['brand_whr_compact'] = $brandNorm . '|' . normSize("$width $height r$rim");
    }
    if ($brandNorm !== '' && $pattern !== '') {
        $keys['brand_pattern'] = $brandNorm . '|' . norm($pattern);
    }

    $out[] = [
        'entity_id' => $eid,
        'sku' => $row['sku'],
        'has_image' => $hasImage,
        'brand' => $brand,
        'tyre_size' => $size,
        'width' => $width,
        'height' => $height,
        'rim' => $rim,
        'pattern' => $pattern,
        'name' => $name,
        'match_keys' => $keys,
    ];
}

fwrite(STDOUT, json_encode($out, JSON_UNESCAPED_UNICODE));
