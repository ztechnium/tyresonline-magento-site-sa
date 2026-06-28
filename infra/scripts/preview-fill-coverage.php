#!/usr/bin/env php
<?php
declare(strict_types=1);
/** Preview fill coverage without DB writes. */

$ksa = json_decode(file_get_contents($argv[1]), true);
$uae = json_decode(file_get_contents($argv[2]), true);
$mediaRoot = '/var/www/magento/pub/media';

function magentoMediaExists(string $mediaRoot, string $file): bool {
    $file = ltrim($file, '/');
    if (str_starts_with($file, 'catalog/product/')) {
        return is_file(rtrim($mediaRoot, '/') . '/' . $file);
    }
    return is_file(rtrim($mediaRoot, '/') . '/catalog/product/' . $file);
}

$uaeIndex = [];
foreach ($uae as $row) {
    $uaeIndex[$row['match_type'] . '::' . $row['match_key']] = $row;
}

$keyPriority = ['brand_size','brand_size_compact','brand_whr','brand_whr_compact','brand_pattern'];
$stats = ['need'=>0,'match'=>0,'match_file'=>0,'match_no_file'=>0,'no_match'=>0];
$byStrategy = [];

foreach ($ksa as $p) {
    if (!empty($p['has_image'])) continue;
    $stats['need']++;
    $matched = null; $strategy = null;
    foreach ($keyPriority as $type) {
        $key = $p['match_keys'][$type] ?? null;
        if (!$key) continue;
        $c = $type . '::' . $key;
        if (isset($uaeIndex[$c])) { $matched = $uaeIndex[$c]; $strategy = $type; break; }
    }
    if (!$matched) { $stats['no_match']++; continue; }
    $stats['match']++;
    $byStrategy[$strategy] = ($byStrategy[$strategy] ?? 0) + 1;
    $hasFile = false;
    foreach ($matched['images'] ?? [] as $img) {
        if (magentoMediaExists($mediaRoot, $img['file'])) { $hasFile = true; break; }
    }
    if ($hasFile) $stats['match_file']++;
    else $stats['match_no_file']++;
}

foreach ($stats as $k=>$v) echo "$k=$v\n";
foreach ($byStrategy as $k=>$v) echo "strategy_$k=$v\n";
