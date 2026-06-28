#!/usr/bin/env php
<?php
declare(strict_types=1);
/**
 * Match local media files to products by brand/pattern tokens in filename.
 * Fallback when UAE key matching fails.
 *
 * Usage: sudo -u www-data php match-local-media-by-name.php /tmp/ksa-products-full.json [--dry-run]
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

// Build basename index (skip cache dirs)
$index = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($mediaRoot, FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    if (!$f->isFile()) continue;
    $path = str_replace('\\', '/', $f->getPathname());
    if (str_contains($path, '/cache/')) continue;
    $ext = strtolower($f->getExtension());
    if (!in_array($ext, ['jpg','jpeg','png','webp'], true)) continue;
    $rel = ltrim(substr($path, strlen($mediaRoot)), '/');
    $basename = strtolower(pathinfo($rel, PATHINFO_FILENAME));
    $index[$basename] = '/' . $rel;
}

function tokens(string $s): array {
    $s = strtolower(preg_replace('/[^a-z0-9]+/', ' ', $s));
    $parts = array_filter(explode(' ', $s), fn($p) => strlen($p) >= 3);
    return array_values(array_unique($parts));
}

function scoreMatch(array $brandTokens, array $patternTokens, string $basename): int {
    $score = 0;
    foreach ($brandTokens as $t) {
        if (str_contains($basename, $t)) $score += 3;
    }
    foreach ($patternTokens as $t) {
        if (str_contains($basename, $t)) $score += 2;
    }
    return $score;
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

function productHasImage(PDO $pdo, int $entityId, int $imageAttrId): bool {
    $s = $pdo->prepare('SELECT value FROM catalog_product_entity_varchar WHERE entity_id=? AND attribute_id=? AND store_id=0 LIMIT 1');
    $s->execute([$entityId, $imageAttrId]);
    $v = (string)($s->fetchColumn() ?: '');
    return $v !== '' && $v !== 'no_selection';
}

if (!$dryRun) $pdo->beginTransaction();
try {
    foreach ($ksa as $p) {
        $eid = (int)$p['entity_id'];
        if (productHasImage($pdo, $eid, $imageAttrId)) continue;
        $brand = tokens($p['brand'] ?? '');
        $pattern = tokens($p['pattern'] ?? '');
        if (!$brand) { $skipped++; continue; }

        $best = null; $bestScore = 0;
        foreach ($index as $basename => $file) {
            $sc = scoreMatch($brand, $pattern, $basename);
            if ($sc > $bestScore) { $bestScore = $sc; $best = $file; }
        }
        // require brand match + (pattern match or score >= 5)
        if ($bestScore < 5 || !$best) { $skipped++; continue; }

        $eid = (int)$p['entity_id'];
        $vid = getOrCreateValueId($pdo, $best, $mediaGalleryAttrId, $dryRun);
        linkGallery($pdo, $eid, $vid, $dryRun);
        upsertVarchar($pdo, $eid, $imageAttrId, $best, $dryRun);
        upsertVarchar($pdo, $eid, $smallImageAttrId, $best, $dryRun);
        upsertVarchar($pdo, $eid, $thumbnailAttrId, $best, $dryRun);
        $filled++;
    }
    if (!$dryRun) $pdo->commit();
} catch (Throwable $e) {
    if (!$dryRun && $pdo->inTransaction()) $pdo->rollBack();
    throw $e;
}
echo "filled=$filled skipped=$skipped dry_run=" . ($dryRun ? '1' : '0') . "\n";
