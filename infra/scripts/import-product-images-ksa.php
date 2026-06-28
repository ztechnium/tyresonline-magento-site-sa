#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Import UAE product images into KSA catalog (gallery DB rows + image attributes).
 *
 * Usage on KSA server:
 *   sudo -u www-data php import-product-images-ksa.php \
 *     /tmp/ksa-products-keys.json /tmp/uae-images-by-key.json [--dry-run]
 *
 * Prerequisites:
 *   - Media files synced from UAE (sync-media-uae-to-ksa.sh)
 *   - JSON exports from export-ksa-match-keys.php and export-uae-images-by-match-key.php
 */

$dryRun = in_array('--dry-run', $argv, true);
$args = array_values(array_filter(array_slice($argv, 1), fn($a) => $a !== '--dry-run'));

if (count($args) < 2) {
    fwrite(STDERR, "Usage: php import-product-images-ksa.php ksa-keys.json uae-images.json [--dry-run]\n");
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

function magentoMediaPath(string $mediaRoot, string $file): string
{
    $file = ltrim($file, '/');
    if (str_starts_with($file, 'catalog/product/')) {
        return rtrim($mediaRoot, '/') . '/' . $file;
    }
    return rtrim($mediaRoot, '/') . '/catalog/product/' . $file;
}

function magentoMediaExists(string $mediaRoot, string $file): bool
{
    return is_file(magentoMediaPath($mediaRoot, $file));
}

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

$imageAttrId = attributeId($pdo, 'image');
$smallImageAttrId = attributeId($pdo, 'small_image');
$thumbnailAttrId = attributeId($pdo, 'thumbnail');
$mediaGalleryAttrId = attributeId($pdo, 'media_gallery');

$uaeByKey = [];
foreach ($uaeImages as $row) {
    $uaeByKey[$row['match_key']] = $row;
}

function mediaPathExists(string $mediaRoot, string $file): bool
{
    return magentoMediaExists($mediaRoot, $file);
}

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
        'SELECT value_id FROM catalog_product_entity_varchar
         WHERE entity_id = ? AND attribute_id = ? AND store_id = 0 LIMIT 1'
    );
    $select->execute([$entityId, $attributeId]);
    $valueId = $select->fetchColumn();
    if ($valueId) {
        $pdo->prepare('UPDATE catalog_product_entity_varchar SET value = ? WHERE value_id = ?')
            ->execute([$value, $valueId]);
    } else {
        $pdo->prepare(
            'INSERT INTO catalog_product_entity_varchar (attribute_id, store_id, entity_id, value)
             VALUES (?, 0, ?, ?)'
        )->execute([$attributeId, $entityId, $value]);
    }
}

function linkGalleryToProduct(
    PDO $pdo,
    int $entityId,
    int $valueId,
    string $label,
    int $position,
    bool $dryRun
): void {
    if ($dryRun || $valueId < 0) {
        return;
    }
    $pdo->prepare(
        'INSERT IGNORE INTO catalog_product_entity_media_gallery_value_to_entity (value_id, entity_id)
         VALUES (?, ?)'
    )->execute([$valueId, $entityId]);

    $select = $pdo->prepare(
        'SELECT value_id FROM catalog_product_entity_media_gallery_value
         WHERE value_id = ? AND store_id = 0 AND entity_id = ? LIMIT 1'
    );
    $select->execute([$valueId, $entityId]);
    if ($select->fetchColumn()) {
        $pdo->prepare(
            'UPDATE catalog_product_entity_media_gallery_value
             SET label = ?, position = ?, disabled = 0
             WHERE value_id = ? AND store_id = 0 AND entity_id = ?'
        )->execute([$label, $position, $valueId, $entityId]);
    } else {
        $pdo->prepare(
            'INSERT INTO catalog_product_entity_media_gallery_value
             (value_id, store_id, entity_id, label, position, disabled)
             VALUES (?, 0, ?, ?, ?, 0)'
        )->execute([$valueId, $entityId, $label, $position]);
    }
}

$stats = [
    'products_matched' => 0,
    'products_skipped_no_uae' => 0,
    'products_skipped_no_file' => 0,
    'products_skipped_has_gallery' => 0,
    'images_linked' => 0,
    'files_missing' => 0,
];

$checkExisting = $pdo->query(
    'SELECT entity_id FROM catalog_product_entity_media_gallery_value_to_entity GROUP BY entity_id'
)->fetchAll(PDO::FETCH_COLUMN);
$hasGallery = array_flip($checkExisting);

if (!$dryRun) {
    $pdo->beginTransaction();
}

try {
    foreach ($ksaProducts as $product) {
        $entityId = (int) $product['entity_id'];
        $matchKey = $product['match_key'];

        if (isset($hasGallery[$entityId])) {
            $stats['products_skipped_has_gallery']++;
            continue;
        }

        if (!isset($uaeByKey[$matchKey])) {
            $stats['products_skipped_no_uae']++;
            continue;
        }

        $images = $uaeByKey[$matchKey]['images'] ?? [];
        if (!$images) {
            $stats['products_skipped_no_uae']++;
            continue;
        }

        $usable = [];
        foreach ($images as $img) {
            if (mediaPathExists($mediaRoot, $img['file'])) {
                $usable[] = $img;
            } else {
                $stats['files_missing']++;
            }
        }

        if (!$usable) {
            $stats['products_skipped_no_file']++;
            continue;
        }

        $stats['products_matched']++;
        $primaryFile = $usable[0]['file'];

        foreach ($usable as $img) {
            $valueId = getOrCreateValueId($pdo, $img['file'], $img['media_type'] ?? 'image', $mediaGalleryAttrId, $dryRun);
            linkGalleryToProduct(
                $pdo,
                $entityId,
                $valueId,
                $img['label'] ?? '',
                (int) ($img['position'] ?? 1),
                $dryRun
            );
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
    echo "$k=$v\n";
}
echo $dryRun ? "dry_run=1\n" : "dry_run=0\n";
