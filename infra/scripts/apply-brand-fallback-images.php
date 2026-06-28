#!/usr/bin/env php
<?php
declare(strict_types=1);
/**
 * Apply brand logo as product image for products still missing images.
 * Copies brand image into catalog/product/brand-fallback/ so Magento URLs work.
 *
 * Usage: sudo -u www-data php apply-brand-fallback-images.php /tmp/ksa-products-full.json [--dry-run]
 */

$dryRun = in_array('--dry-run', $argv, true);
$ksa = json_decode(file_get_contents($argv[1]), true, 512, JSON_THROW_ON_ERROR);
$mediaRoot = '/var/www/magento/pub/media';
$fallbackDir = $mediaRoot . '/catalog/product/brand-fallback';

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

$brandImages = [];
foreach ($pdo->query('SELECT name, image FROM mgs_brand WHERE image IS NOT NULL AND image != ""') as $r) {
    $brandImages[strtolower(trim($r['name']))] = ltrim($r['image'], '/');
}

function slug(string $s): string {
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-') ?: 'unknown';
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

function linkGallery(PDO $pdo, int $eid, int $vid, string $label, bool $dryRun): void {
    if ($dryRun || $vid < 0) return;
    $pdo->prepare('INSERT IGNORE INTO catalog_product_entity_media_gallery_value_to_entity (value_id,entity_id) VALUES (?,?)')
        ->execute([$vid, $eid]);
    $s = $pdo->prepare('SELECT value_id FROM catalog_product_entity_media_gallery_value WHERE value_id=? AND entity_id=? AND store_id=0');
    $s->execute([$vid, $eid]);
    if (!$s->fetchColumn()) {
        $pdo->prepare('INSERT INTO catalog_product_entity_media_gallery_value (value_id,store_id,entity_id,label,position,disabled) VALUES (?,0,?,?,1,0)')
            ->execute([$vid, $eid, $label]);
    }
}

$filled = 0; $skipped = 0; $copied = 0;

function productHasImage(PDO $pdo, int $entityId, int $imageAttrId): bool {
    $s = $pdo->prepare('SELECT value FROM catalog_product_entity_varchar WHERE entity_id=? AND attribute_id=? AND store_id=0 LIMIT 1');
    $s->execute([$entityId, $imageAttrId]);
    $v = (string)($s->fetchColumn() ?: '');
    return $v !== '' && $v !== 'no_selection';
}

if (!$dryRun && !is_dir($fallbackDir)) {
    mkdir($fallbackDir, 0775, true);
}
if (!$dryRun) $pdo->beginTransaction();

try {
    foreach ($ksa as $p) {
        $eid = (int)$p['entity_id'];
        if (productHasImage($pdo, $eid, $imageAttrId)) continue;
        $brandKey = strtolower(trim($p['brand'] ?? ''));
        if (!$brandKey || !isset($brandImages[$brandKey])) {
            $skipped++;
            continue;
        }

        $src = $mediaRoot . '/' . $brandImages[$brandKey];
        if (!is_file($src)) {
            $skipped++;
            continue;
        }

        $ext = pathinfo($src, PATHINFO_EXTENSION) ?: 'png';
        $destRel = 'brand-fallback/' . slug($brandKey) . '.' . $ext;
        $dest = $fallbackDir . '/' . slug($brandKey) . '.' . $ext;
        $galleryPath = '/brand-fallback/' . slug($brandKey) . '.' . $ext;

        if (!is_file($dest)) {
            if (!$dryRun) {
                copy($src, $dest);
                @chmod($dest, 0664);
            }
            $copied++;
        }

        $eid = (int)$p['entity_id'];
        $vid = getOrCreateValueId($pdo, $galleryPath, $mediaGalleryAttrId, $dryRun);
        linkGallery($pdo, $eid, $vid, $p['brand'], $dryRun);
        upsertVarchar($pdo, $eid, $imageAttrId, $galleryPath, $dryRun);
        upsertVarchar($pdo, $eid, $smallImageAttrId, $galleryPath, $dryRun);
        upsertVarchar($pdo, $eid, $thumbnailAttrId, $galleryPath, $dryRun);
        $filled++;
    }
    if (!$dryRun) $pdo->commit();
} catch (Throwable $e) {
    if (!$dryRun && $pdo->inTransaction()) $pdo->rollBack();
    throw $e;
}

echo "filled=$filled skipped=$skipped brand_files_copied=$copied dry_run=" . ($dryRun ? '1' : '0') . "\n";
