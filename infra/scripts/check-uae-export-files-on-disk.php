#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Check how many UAE image paths from export exist on local disk (with Magento path rules).
 */
$mediaRoot = '/var/www/magento/pub/media';
$data = json_decode(file_get_contents('/tmp/uae-images-by-key.json'), true);

function resolveMediaPath(string $mediaRoot, string $file): ?string
{
    $file = ltrim($file, '/');
    $candidates = [
        $mediaRoot . '/' . $file,
        $mediaRoot . '/catalog/product/' . $file,
    ];
    foreach ($candidates as $path) {
        if (is_file($path)) {
            return $path;
        }
    }
    return null;
}

$total = 0;
$found = 0;
$byPrefix = [];

foreach ($data as $row) {
    foreach ($row['images'] ?? [] as $img) {
        $total++;
        $file = $img['file'];
        $prefix = explode('/', trim($file, '/'))[0] ?? 'unknown';
        if (!isset($byPrefix[$prefix])) {
            $byPrefix[$prefix] = ['total' => 0, 'found' => 0];
        }
        $byPrefix[$prefix]['total']++;
        if (resolveMediaPath($mediaRoot, $file)) {
            $found++;
            $byPrefix[$prefix]['found']++;
        }
    }
}

echo "total_images=$total found_on_disk=$found missing=" . ($total - $found) . "\n";
ksort($byPrefix);
foreach ($byPrefix as $prefix => $stats) {
    echo "$prefix: {$stats['found']}/{$stats['total']}\n";
}
