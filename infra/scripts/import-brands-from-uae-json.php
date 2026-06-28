#!/usr/bin/env php
<?php
declare(strict_types=1);
/**
 * Import brands JSON exported from UAE + download missing media from UAE.
 * Usage: sudo -u www-data php infra/scripts/import-brands-from-uae-json.php /tmp/uae-brands-full.json
 */
require __DIR__ . '/../../app/bootstrap.php';
$om = Magento\Framework\App\Bootstrap::create(BP, $_SERVER)->getObjectManager();
try {
    $om->get(Magento\Framework\App\State::class)->setAreaCode('adminhtml');
} catch (\Exception $e) {
}

$jsonFile = $argv[1] ?? '';
if (!$jsonFile || !is_readable($jsonFile)) {
    fwrite(STDERR, "Usage: import-brands-from-uae-json.php /path/to/uae-brands-full.json\n");
    exit(1);
}

$payload = json_decode(file_get_contents($jsonFile), true, 512, JSON_THROW_ON_ERROR);
$brands = $payload['brands'] ?? [];
$storeRows = $payload['stores'] ?? [];

$conn = $om->get(Magento\Framework\App\ResourceConnection::class)->getConnection();
$columns = array_column($conn->describeTable('mgs_brand'), 'COLUMN_NAME');
$skipCols = ['brand_id'];
$insertCols = array_values(array_filter($columns, static fn ($c) => !in_array($c, $skipCols, true)));

$ksaByUrl = [];
foreach ($conn->fetchAll('SELECT brand_id, url_key FROM mgs_brand') as $row) {
    $ksaByUrl[$row['url_key']] = (int)$row['brand_id'];
}

$storeMap = [];
foreach ($storeRows as $row) {
    $storeMap[(int)$row['brand_id']][] = (int)$row['store_id'];
}

$mediaRoot = BP . '/pub/media/';
$uaeMediaBase = getenv('UAE_MEDIA_URL') ?: 'https://stg.tyresonline.ae/media/';

$inserted = 0;
$updated = 0;
$downloaded = 0;

foreach ($brands as $brand) {
    $data = [];
    foreach ($insertCols as $col) {
        if (array_key_exists($col, $brand)) {
            $data[$col] = $brand[$col];
        }
    }

    foreach (['image', 'small_image'] as $imgCol) {
        if (empty($data[$imgCol])) {
            continue;
        }
        $rel = ltrim((string)$data[$imgCol], '/');
        $local = $mediaRoot . $rel;
        if (!is_file($local)) {
            $dir = dirname($local);
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            $srcUrl = rtrim($uaeMediaBase, '/') . '/' . $rel;
            $ctx = stream_context_create(['http' => ['timeout' => 45, 'header' => "User-Agent: TyresOnline-Sync\r\n"]]);
            $bin = @file_get_contents($srcUrl, false, $ctx);
            if ($bin !== false && strlen($bin) > 100) {
                file_put_contents($local, $bin);
                @chmod($local, 0664);
                $downloaded++;
            } else {
                echo "WARN download failed: {$srcUrl}\n";
            }
        }
    }

    $urlKey = $brand['url_key'];
    if (isset($ksaByUrl[$urlKey])) {
        $brandId = $ksaByUrl[$urlKey];
        $conn->update('mgs_brand', $data, ['brand_id = ?' => $brandId]);
        $updated++;
    } else {
        $conn->insert('mgs_brand', $data);
        $brandId = (int)$conn->lastInsertId();
        $ksaByUrl[$urlKey] = $brandId;
        $inserted++;
    }

    $conn->delete('mgs_brand_store', ['brand_id = ?' => $brandId]);
    $uaeBrandId = (int)$brand['brand_id'];
    foreach ($storeMap[$uaeBrandId] ?? [0, 1, 2] as $storeId) {
        $conn->insert('mgs_brand_store', ['brand_id' => $brandId, 'store_id' => $storeId]);
    }
}

$uaeUrlKeys = array_column($brands, 'url_key');
$disabled = 0;
foreach ($conn->fetchAll('SELECT brand_id, url_key, name FROM mgs_brand WHERE status = 1') as $row) {
    if (!in_array($row['url_key'], $uaeUrlKeys, true)) {
        $conn->update('mgs_brand', ['status' => 0], ['brand_id = ?' => $row['brand_id']]);
        $disabled++;
    }
}

echo "Done inserted={$inserted} updated={$updated} disabled={$disabled} media_downloaded={$downloaded} uae_total=" . count($brands) . "\n";
