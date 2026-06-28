#!/usr/bin/env php
<?php
declare(strict_types=1);
/**
 * Assign generic placeholder image to all products still missing images.
 *
 * Usage: sudo -u www-data php apply-generic-placeholder.php [--dry-run]
 */

$dryRun = in_array('--dry-run', $argv, true);
$mediaRoot = '/var/www/magento/pub/media';
$srcCandidates = [
    $mediaRoot . '/images/placeholder.png',
    $mediaRoot . '/mgs_brand/no_image.png',
];
$destRel = '/placeholder/tyre-placeholder.png';
$dest = $mediaRoot . '/catalog/product/placeholder/tyre-placeholder.png';

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

$src = null;
foreach ($srcCandidates as $c) {
    if (is_file($c)) { $src = $c; break; }
}
if (!$src) {
    fwrite(STDERR, "No placeholder source found\n");
    exit(1);
}

if (!$dryRun) {
    if (!is_dir(dirname($dest))) {
        mkdir(dirname($dest), 0775, true);
    }
    if (!is_file($dest)) {
        copy($src, $dest);
        @chmod($dest, 0664);
    }
}

$entityIds = $pdo->query("
SELECT cpe.entity_id FROM catalog_product_entity cpe
LEFT JOIN catalog_product_entity_varchar img ON img.entity_id=cpe.entity_id
  AND img.attribute_id=$imageAttrId AND img.store_id=0
WHERE img.value IS NULL OR img.value='' OR img.value='no_selection'
")->fetchAll(PDO::FETCH_COLUMN);

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
            ->execute([$vid, $eid, 'Placeholder']);
    }
}

$filled = 0;
if (!$dryRun) $pdo->beginTransaction();
try {
    $vid = getOrCreateValueId($pdo, $destRel, $mediaGalleryAttrId, $dryRun);
    foreach ($entityIds as $eid) {
        $eid = (int)$eid;
        linkGallery($pdo, $eid, $vid, $dryRun);
        upsertVarchar($pdo, $eid, $imageAttrId, $destRel, $dryRun);
        upsertVarchar($pdo, $eid, $smallImageAttrId, $destRel, $dryRun);
        upsertVarchar($pdo, $eid, $thumbnailAttrId, $destRel, $dryRun);
        $filled++;
    }
    if (!$dryRun) $pdo->commit();
} catch (Throwable $e) {
    if (!$dryRun && $pdo->inTransaction()) $pdo->rollBack();
    throw $e;
}
echo "filled=$filled placeholder=$destRel dry_run=" . ($dryRun ? '1' : '0') . "\n";
