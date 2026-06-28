#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Fill remaining KSA product images using multi-key UAE matching + brand fallback.
 *
 * Usage:
 *   sudo -u www-data php fill-product-images-ksa.php \
 *     /tmp/ksa-products-full.json /tmp/uae-images-full.json [--dry-run] [--brand-fallback]
 */

$dryRun = in_array('--dry-run', $argv, true);
$brandFallback = in_array('--brand-fallback', $argv, true);
$args = array_values(array_filter(
    array_slice($argv, 1),
    fn($a) => !in_array($a, ['--dry-run', '--brand-fallback'], true)
));

if (count($args) < 2) {
    fwrite(STDERR, "Usage: php fill-product-images-ksa.php ksa-full.json uae-full.json [--dry-run] [--brand-fallback]\n");
    exit(1);
}

$ksaProducts = json_decode(file_get_contents($args[0]), true, 512, JSON_THROW_ON_ERROR);
$uaeImages = json_decode(file_get_contents($args[1]), true, 512, JSON_THROW_ON_ERROR);

$env = include '/var/www/magento/app/etc/env.php';
$db = $env['db']['connection']['default'];
$pdo = new PDO(
    'mysql:host=' . $db['host'] . ';dbname=' . $db['dbname'],
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$mediaRoot = '/var/www/magento/pub/media';

function magentoMediaExists(string $mediaRoot, string $file): bool
{
    $file = ltrim($file, '/');
    if (str_starts_with($file, 'catalog/product/')) {
        return is_file(rtrim($mediaRoot, '/') . '/' . $file);
    }
    return is_file(rtrim($mediaRoot, '/') . '/catalog/product/' . $file);
}

function attributeId(PDO $pdo, string $code): int
{
    static $cache = [];
    if (!isset($cache[$code])) {
        $stmt = $pdo->prepare('SELECT attribute_id FROM eav_attribute WHERE attribute_code = ? AND entity_type_id = 4');
        $stmt->execute([$code]);
        $cache[$code] = (int) $stmt->fetchColumn();
    }
    return $cache[$code];
}

$imageAttrId = attributeId($pdo, 'image');
$smallImageAttrId = attributeId($pdo, 'small_image');
$thumbnailAttrId = attributeId($pdo, 'thumbnail');
$mediaGalleryAttrId = attributeId($pdo, 'media_gallery');

function productHasImage(PDO $pdo, int $entityId, int $imageAttrId): bool
{
    static $cache = [];
    if (!array_key_exists($entityId, $cache)) {
        $stmt = $pdo->prepare(
            'SELECT value FROM catalog_product_entity_varchar WHERE entity_id = ? AND attribute_id = ? AND store_id = 0 LIMIT 1'
        );
        $stmt->execute([$entityId, $imageAttrId]);
        $val = (string) ($stmt->fetchColumn() ?: '');
        $cache[$entityId] = $val !== '' && $val !== 'no_selection';
    }
    return $cache[$entityId];
}

// Index UAE images by match_type::match_key
$uaeIndex = [];
foreach ($uaeImages as $row) {
    $composite = $row['match_type'] . '::' . $row['match_key'];
    if (!isset($uaeIndex[$composite])) {
        $uaeIndex[$composite] = $row;
    }
}

// Brand fallback images
$brandImages = [];
if ($brandFallback) {
    $stmt = $pdo->query('SELECT name, image FROM mgs_brand WHERE image IS NOT NULL AND image != ""');
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $brandImages[strtolower(trim($r['name']))] = '/' . ltrim($r['image'], '/');
    }
}

$keyPriority = [
    'brand_size',
    'brand_size_compact',
    'brand_whr',
    'brand_whr_compact',
    'brand_pattern',
];

function getOrCreateValueId(PDO $pdo, string $file, string $mediaType, int $mediaGalleryAttrId, bool $dryRun): int
{
    $stmt = $pdo->prepare('SELECT value_id FROM catalog_product_entity_media_gallery WHERE value = ? LIMIT 1');
    $stmt->execute([$file]);
    $existing = $stmt->fetchColumn();
    if ($existing) {
        return (int) $existing;
    }
    if ($dryRun) {
        return -1;
    }
    $insert = $pdo->prepare(
        'INSERT INTO catalog_product_entity_media_gallery (attribute_id, value, media_type) VALUES (?, ?, ?)'
    );
    $insert->execute([$mediaGalleryAttrId, $file, $mediaType ?: 'image']);
    return (int) $pdo->lastInsertId();
}

function upsertVarchar(PDO $pdo, int $entityId, int $attributeId, string $value, bool $dryRun): void
{
    if ($dryRun) {
        return;
    }
    $select = $pdo->prepare(
        'SELECT value_id FROM catalog_product_entity_varchar WHERE entity_id = ? AND attribute_id = ? AND store_id = 0 LIMIT 1'
    );
    $select->execute([$entityId, $attributeId]);
    $valueId = $select->fetchColumn();
    if ($valueId) {
        $pdo->prepare('UPDATE catalog_product_entity_varchar SET value = ? WHERE value_id = ?')->execute([$value, $valueId]);
    } else {
        $pdo->prepare(
            'INSERT INTO catalog_product_entity_varchar (attribute_id, store_id, entity_id, value) VALUES (?, 0, ?, ?)'
        )->execute([$attributeId, $entityId, $value]);
    }
}

function linkGalleryToProduct(PDO $pdo, int $entityId, int $valueId, string $label, int $position, bool $dryRun): void
{
    if ($dryRun || $valueId < 0) {
        return;
    }
    $pdo->prepare(
        'INSERT IGNORE INTO catalog_product_entity_media_gallery_value_to_entity (value_id, entity_id) VALUES (?, ?)'
    )->execute([$valueId, $entityId]);

    $select = $pdo->prepare(
        'SELECT value_id FROM catalog_product_entity_media_gallery_value WHERE value_id = ? AND store_id = 0 AND entity_id = ? LIMIT 1'
    );
    $select->execute([$valueId, $entityId]);
    if ($select->fetchColumn()) {
        $pdo->prepare(
            'UPDATE catalog_product_entity_media_gallery_value SET label = ?, position = ?, disabled = 0 WHERE value_id = ? AND store_id = 0 AND entity_id = ?'
        )->execute([$label, $position, $valueId, $entityId]);
    } else {
        $pdo->prepare(
            'INSERT INTO catalog_product_entity_media_gallery_value (value_id, store_id, entity_id, label, position, disabled) VALUES (?, 0, ?, ?, ?, 0)'
        )->execute([$valueId, $entityId, $label, $position]);
    }
}

function brandImageExists(string $mediaRoot, string $file): bool
{
    $file = ltrim($file, '/');
    return is_file(rtrim($mediaRoot, '/') . '/' . $file);
}

$stats = [
    'filled' => 0,
    'skipped_has_image' => 0,
    'skipped_no_match' => 0,
    'skipped_no_file' => 0,
    'brand_fallback' => 0,
    'by_strategy' => [],
    'images_linked' => 0,
    'files_missing' => 0,
];

if (!$dryRun) {
    $pdo->beginTransaction();
}

try {
    foreach ($ksaProducts as $product) {
        $entityId = (int) $product['entity_id'];
        if (productHasImage($pdo, $entityId, $imageAttrId)) {
            $stats['skipped_has_image']++;
            continue;
        }
        $matched = null;
        $strategy = null;

        foreach ($keyPriority as $type) {
            $key = $product['match_keys'][$type] ?? null;
            if (!$key) {
                continue;
            }
            $composite = $type . '::' . $key;
            if (isset($uaeIndex[$composite])) {
                $matched = $uaeIndex[$composite];
                $strategy = $type;
                break;
            }
        }

        $images = [];
        if ($matched) {
            $images = $matched['images'] ?? [];
        } elseif ($brandFallback) {
            $brandKey = strtolower(trim($product['brand'] ?? ''));
            if ($brandKey && isset($brandImages[$brandKey])) {
                $bf = $brandImages[$brandKey];
                if (brandImageExists($mediaRoot, $bf)) {
                    $images = [['file' => $bf, 'media_type' => 'image', 'label' => $product['brand'], 'position' => 1]];
                    $strategy = 'brand_fallback';
                }
            }
        }

        if (!$images) {
            $stats['skipped_no_match']++;
            continue;
        }

        $usable = [];
        foreach ($images as $img) {
            $file = $img['file'];
            if (str_starts_with(ltrim($file, '/'), 'mgs_brand/')) {
                if (brandImageExists($mediaRoot, $file)) {
                    $usable[] = $img;
                } else {
                    $stats['files_missing']++;
                }
            } elseif (magentoMediaExists($mediaRoot, $file)) {
                $usable[] = $img;
            } else {
                $stats['files_missing']++;
            }
        }

        if (!$usable) {
            $stats['skipped_no_file']++;
            continue;
        }

        $stats['filled']++;
        $stats['by_strategy'][$strategy] = ($stats['by_strategy'][$strategy] ?? 0) + 1;
        if ($strategy === 'brand_fallback') {
            $stats['brand_fallback']++;
        }

        $primaryFile = $usable[0]['file'];
        foreach ($usable as $img) {
            $valueId = getOrCreateValueId($pdo, $img['file'], $img['media_type'] ?? 'image', $mediaGalleryAttrId, $dryRun);
            linkGalleryToProduct($pdo, $entityId, $valueId, $img['label'] ?? '', (int) ($img['position'] ?? 1), $dryRun);
            $stats['images_linked']++;
        }

        upsertVarchar($pdo, $entityId, $imageAttrId, $primaryFile, $dryRun);
        upsertVarchar($pdo, $entityId, $smallImageAttrId, $primaryFile, $dryRun);
        upsertVarchar($pdo, $entityId, $thumbnailAttrId, $primaryFile, $dryRun);
    }

    if (!$dryRun) {
        $pdo->commit();
    }
} catch (Throwable $e) {
    if (!$dryRun && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}

foreach ($stats as $k => $v) {
    if (is_array($v)) {
        foreach ($v as $sk => $sv) {
            echo "strategy_{$sk}=$sv\n";
        }
    } else {
        echo "$k=$v\n";
    }
}
echo $dryRun ? "dry_run=1\n" : "dry_run=0\n";
