#!/usr/bin/env php
<?php
declare(strict_types=1);
/** Extract image paths needed for KSA products still missing images. */

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

function relPath(string $file): string {
    $file = ltrim($file, '/');
    if (str_starts_with($file, 'catalog/product/')) {
        return substr($file, strlen('catalog/product/'));
    }
    return $file;
}

$uaeIndex = [];
foreach ($uae as $row) {
    $uaeIndex[$row['match_type'] . '::' . $row['match_key']] = $row;
}

$keyPriority = ['brand_size','brand_size_compact','brand_whr','brand_whr_compact','brand_pattern'];
$needed = [];

foreach ($ksa as $p) {
    if (!empty($p['has_image'])) continue;
    $matched = null;
    foreach ($keyPriority as $type) {
        $key = $p['match_keys'][$type] ?? null;
        if (!$key) continue;
        if (isset($uaeIndex[$type . '::' . $key])) {
            $matched = $uaeIndex[$type . '::' . $key];
            break;
        }
    }
    if (!$matched) continue;
    foreach ($matched['images'] ?? [] as $img) {
        if (!magentoMediaExists($mediaRoot, $img['file'])) {
            $needed[relPath($img['file'])] = true;
        }
    }
}

$paths = array_keys($needed);
sort($paths);
file_put_contents('/tmp/needed-image-paths.txt', implode("\n", $paths) . "\n");
echo 'needed_paths=' . count($paths) . "\n";
