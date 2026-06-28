#!/usr/bin/env php
<?php
declare(strict_types=1);
/**
 * Assign a local tyre image by brand name for products still missing images.
 * Uses files under catalog/product/car-tyres and tyresonline-tyres.
 *
 * Usage: sudo -u www-data php assign-brand-local-image.php /tmp/ksa-products-full.json [--dry-run]
 */

$dryRun = in_array('--dry-run', $argv, true);
$ksa = json_decode(file_get_contents($argv[1]), true, 512, JSON_THROW_ON_ERROR);
$mediaRoot = '/var/www/magento/pub/media/catalog/product';

$env = include '/var/www/magento/app/etc/env.php';
$db = $env['db']['connection']['default'];
$pdo = new PDO('mysql:host='.$db['host'].';dbname='.$db['dbname'], $db['username'], $db['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

function attributeId(PDO $pdo, string $code): int {
    static $c = [];
    if (!isset($c[$code])) {
        $s = $pdo->prepare('SELECT attribute_id FROM eav_attribute WHERE attribute_code=? AND entity_type_id=4');
        $s->execute([$code]);
        $c[$code] = (int)$s->fetchColumn();
    }
    return $c[$code];
}

$imageAttrId = attributeId($pdo, 'image');
$smallImageAttrId = attributeId($pdo, 'small_image');
$thumbnailAttrId = attributeId($pdo, 'thumbnail');
$mediaGalleryAttrId = attributeId($pdo, 'media_gallery');

function productHasImage(PDO $pdo, int $entityId, int $imageAttrId): bool {
    $s = $pdo->prepare('SELECT value FROM catalog_product_entity_varchar WHERE entity_id=? AND attribute_id=? AND store_id=0 LIMIT 1');
    $s->execute([$entityId, $imageAttrId]);
    $v = (string)($s->fetchColumn() ?: '');
    return $v !== '' && $v !== 'no_selection';
}

function brandSlug(string $brand): string {
    return strtolower(preg_replace('/[^a-z0-9]+/', '', strtolower($brand)));
}

// Index images by brand slug from car-tyres and tyresonline-tyres folders
$brandFiles = [];
foreach (['car-tyres', 'tyresonline-tyres'] as $folder) {
    $base = $mediaRoot . '/' . $folder;
    if (!is_dir($base)) continue;
    foreach (scandir($base) as $file) {
        if ($file === '.' || $file === '..') continue;
        $full = $base . '/' . $file;
        if (!is_file($full)) continue;
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp'], true)) continue;
        $galleryPath = '/' . $folder . '/' . $file;
        $basename = strtolower(pathinfo($file, PATHINFO_FILENAME));
        // map by first token in filename (usually brand)
        $parts = preg_split('/[^a-z0-9]+/', $basename);
        foreach ($parts as $part) {
            if (strlen($part) >= 4) {
                $brandFiles[$part][] = $galleryPath;
            }
        }
    }
}

function getOrCreateValueId(PDO $pdo, string $file, int $attrId, bool $dryRun): int {
    $s = $pdo->prepare('SELECT value_id FROM catalog_product_entity_media_gallery WHERE value=? LIMIT 1');
    $s->execute([$file]);
    $e = $s->fetchColumn();
    if ($e) return (int)$e;
    if ($dryRun) return -1;
    $pdo->prepare('INSERT INTO catalog_product_entity_media_gallery (attribute_id,value,media_type) VALUES (?,?,?)')
        ->execute([$attrId, $file, 'image']);
    return (int)$pdo->lastInsertId();
}

function upsertVarchar(PDO $pdo, int $eid, int $aid, string $val, bool $dryRun): void {
    if ($dryRun) return;
    $s = $pdo->prepare('SELECT value_id FROM catalog_product_entity_varchar WHERE entity_id=? AND attribute_id=? AND store_id=0');
    $s->execute([$eid, $aid]);
    if ($vid = $s->fetchColumn()) {
        $pdo->prepare('UPDATE catalog_product_entity_varchar SET value=? WHERE value_id=?')->execute([$val, $vid]);
    } else {
        $pdo->prepare('INSERT INTO catalog_product_entity_varchar (attribute_id,store_id,entity_id,value) VALUES (?,0,?,?)')
            ->execute([$aid, $eid, $val]);
    }
}

function linkGallery(PDO $pdo, int $eid, int $vid, bool $dryRun): void {
    if ($dryRun || $vid < 0) return;
    $pdo->prepare('INSERT IGNORE INTO catalog_product_entity_media_gallery_value_to_entity (value_id,entity_id) VALUES (?,?)')
        ->execute([$vid, $eid]);
    $s = $pdo->prepare('SELECT value_id FROM catalog_product_entity_media_gallery_value WHERE value_id=? AND entity_id=? AND store_id=0');
    $s->execute([$vid, $eid]);
    if (!$s->fetchColumn()) {
        $pdo->prepare('INSERT INTO catalog_product_entity_media_gallery_value (value_id,store_id,entity_id,label,position,disabled) VALUES (?,0,?,? ,1,0)')
            ->execute([$vid, $eid, '']);
    }
}

$filled = 0; $skipped = 0;
if (!$dryRun) $pdo->beginTransaction();
try {
    foreach ($ksa as $p) {
        $eid = (int)$p['entity_id'];
        if (productHasImage($pdo, $eid, $imageAttrId)) continue;
        $slug = brandSlug($p['brand'] ?? '');
        if (!$slug || !isset($brandFiles[$slug])) {
            $skipped++;
            continue;
        }
        $file = $brandFiles[$slug][0];
        $vid = getOrCreateValueId($pdo, $file, $mediaGalleryAttrId, $dryRun);
        linkGallery($pdo, $eid, $vid, $dryRun);
        upsertVarchar($pdo, $eid, $imageAttrId, $file, $dryRun);
        upsertVarchar($pdo, $eid, $smallImageAttrId, $file, $dryRun);
        upsertVarchar($pdo, $eid, $thumbnailAttrId, $file, $dryRun);
        $filled++;
    }
    if (!$dryRun) $pdo->commit();
} catch (Throwable $e) {
    if (!$dryRun && $pdo->inTransaction()) $pdo->rollBack();
    throw $e;
}
echo "filled=$filled skipped=$skipped dry_run=" . ($dryRun ? '1' : '0') . "\n";
