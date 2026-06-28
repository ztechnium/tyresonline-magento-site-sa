#!/usr/bin/env php
<?php
/**
 * Sync mgs_brand records (+ store assignments) from UAE staging DB to KSA.
 * Run on KSA server with UAE DB reachable, or pipe UAE export JSON.
 *
 * Usage (on KSA EC2):
 *   sudo -u www-data php infra/scripts/sync-brands-from-uae.php
 */
declare(strict_types=1);

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
try {
    $om->get(\Magento\Framework\App\State::class)->setAreaCode('adminhtml');
} catch (\Exception $e) {
}

$ksaConn = $om->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();

// UAE RDS — same VPC credentials pattern as other infra scripts
$uaeHost = getenv('UAE_DB_HOST') ?: 'tyresonline-ae-stg-rds.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com';
$uaeDb = getenv('UAE_DB_NAME') ?: 'tyresonline_ae';
$uaeUser = getenv('UAE_DB_USER') ?: 'magento';
$uaePass = getenv('UAE_DB_PASS') ?: 'uxaYMIQRwEU0AFl1Ck69LJnH';

$uaePdo = new PDO(
    "mysql:host={$uaeHost};dbname={$uaeDb};charset=utf8mb4",
    $uaeUser,
    $uaePass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$columns = array_column($ksaConn->describeTable('mgs_brand'), 'COLUMN_NAME');
$skipCols = ['brand_id'];
$insertCols = array_values(array_filter($columns, static fn ($c) => !in_array($c, $skipCols, true)));

$uaeBrands = $uaePdo->query('SELECT * FROM mgs_brand ORDER BY brand_id')->fetchAll(PDO::FETCH_ASSOC);
$uaeStores = $uaePdo->query('SELECT brand_id, store_id FROM mgs_brand_store')->fetchAll(PDO::FETCH_ASSOC);
$storeMap = [];
foreach ($uaeStores as $row) {
    $storeMap[(int)$row['brand_id']][] = (int)$row['store_id'];
}

$ksaByUrl = [];
foreach ($ksaConn->fetchAll('SELECT brand_id, url_key FROM mgs_brand') as $row) {
    $ksaByUrl[$row['url_key']] = (int)$row['brand_id'];
}

$inserted = 0;
$updated = 0;
$mediaRoot = BP . '/pub/media/';
$uaeMediaBase = getenv('UAE_MEDIA_URL') ?: 'https://stg.tyresonline.ae/media/';

foreach ($uaeBrands as $brand) {
    $urlKey = $brand['url_key'];
    $data = [];
    foreach ($insertCols as $col) {
        $data[$col] = $brand[$col];
    }

    foreach (['image', 'small_image'] as $imgCol) {
        if (empty($data[$imgCol])) {
            continue;
        }
        $rel = ltrim((string)$data[$imgCol], '/');
        $local = $mediaRoot . $rel;
        if (!is_file($local)) {
            $srcUrl = rtrim($uaeMediaBase, '/') . '/' . $rel;
            $dir = dirname($local);
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            $ctx = stream_context_create(['http' => ['timeout' => 30]]);
            $bin = @file_get_contents($srcUrl, false, $ctx);
            if ($bin !== false) {
                file_put_contents($local, $bin);
                @chmod($local, 0664);
                echo "Downloaded media: {$rel}\n";
            } else {
                echo "WARN missing media: {$srcUrl}\n";
            }
        }
    }

    if (isset($ksaByUrl[$urlKey])) {
        $brandId = $ksaByUrl[$urlKey];
        $ksaConn->update('mgs_brand', $data, ['brand_id = ?' => $brandId]);
        $updated++;
    } else {
        $ksaConn->insert('mgs_brand', $data);
        $brandId = (int)$ksaConn->lastInsertId();
        $ksaByUrl[$urlKey] = $brandId;
        $inserted++;
    }

    $ksaConn->delete('mgs_brand_store', ['brand_id = ?' => $brandId]);
    $uaeBrandId = (int)$brand['brand_id'];
    $stores = $storeMap[$uaeBrandId] ?? [0, 1, 2];
    // Map UAE store IDs to KSA: 0=admin, 1=en, 2=ar typically
    foreach ($stores as $storeId) {
        $ksaConn->insert('mgs_brand_store', ['brand_id' => $brandId, 'store_id' => $storeId]);
    }
}

// Disable KSA-only brands not present on UAE (optional: keep extra brands)
$uaeUrlKeys = array_column($uaeBrands, 'url_key');
$disabled = 0;
foreach ($ksaConn->fetchAll('SELECT brand_id, url_key, name FROM mgs_brand WHERE status = 1') as $row) {
    if (!in_array($row['url_key'], $uaeUrlKeys, true)) {
        $ksaConn->update('mgs_brand', ['status' => 0], ['brand_id = ?' => $row['brand_id']]);
        echo "Disabled extra KSA brand: {$row['name']} ({$row['url_key']})\n";
        $disabled++;
    }
}

echo "Done. inserted={$inserted} updated={$updated} disabled_extra={$disabled} uae_total=" . count($uaeBrands) . "\n";
