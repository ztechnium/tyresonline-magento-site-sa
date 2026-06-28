#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Download product image files from UAE staging into KSA pub/media/catalog/product/.
 *
 * Usage:
 *   php download-images-from-uae-staging.php /tmp/needed-image-paths.txt
 *   php download-images-from-uae-staging.php /tmp/uae-images-full.json
 */

$sourceBase = getenv('UAE_MEDIA_BASE') ?: 'https://stg.tyresonline.ae/media/catalog/product';
$mediaRoot = '/var/www/magento/pub/media/catalog/product';
$dryRun = in_array('--dry-run', $argv, true);
$limit = 0;
$inputFile = '';

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--limit=')) {
        $limit = (int) substr($arg, 8);
    } elseif ($arg !== '--dry-run' && $arg !== $argv[0] && !str_starts_with($arg, '--')) {
        $inputFile = $arg;
    }
}

if ($inputFile === '') {
    fwrite(STDERR, "Usage: php download-images-from-uae-staging.php (paths.txt|uae-images.json) [--dry-run] [--limit=N]\n");
    exit(1);
}

$paths = [];
if (str_ends_with($inputFile, '.txt')) {
    $paths = array_values(array_filter(array_map('trim', file($inputFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [])));
} else {
    $data = json_decode(file_get_contents($inputFile), true, 512, JSON_THROW_ON_ERROR);
    $set = [];
    foreach ($data as $row) {
        foreach ($row['images'] ?? [] as $img) {
            $file = ltrim($img['file'], '/');
            if (str_starts_with($file, 'catalog/product/')) {
                $file = substr($file, strlen('catalog/product/'));
            }
            $set[$file] = true;
        }
    }
    $paths = array_keys($set);
}

sort($paths);
echo 'unique_paths=' . count($paths) . "\n";

$downloaded = 0;
$skipped = 0;
$failed = 0;
$processed = 0;

foreach ($paths as $rel) {
    if ($limit > 0 && $processed >= $limit) {
        break;
    }
    $processed++;
    $dest = $mediaRoot . '/' . $rel;
    if (is_file($dest) && filesize($dest) > 0) {
        $skipped++;
        continue;
    }

    $url = rtrim($sourceBase, '/') . '/' . str_replace(' ', '%20', $rel);
    if ($dryRun) {
        echo "would_download $url\n";
        $downloaded++;
        continue;
    }

    $dir = dirname($dest);
    if (!is_dir($dir)) {
        if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
            $failed++;
            continue;
        }
    }

    $part = $dest . '.part';
    $fp = @fopen($part, 'wb');
    if ($fp === false) {
        $failed++;
        continue;
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_FILE => $fp,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_FAILONERROR => false,
    ]);
    $ok = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    fclose($fp);

    if ($ok && $code === 200 && is_file($part) && filesize($part) > 100) {
        rename($part, $dest);
        @chmod($dest, 0664);
        $downloaded++;
        if ($downloaded % 100 === 0) {
            echo "downloaded=$downloaded skipped=$skipped failed=$failed\n";
        }
    } else {
        @unlink($part);
        $failed++;
    }
}

echo "downloaded=$downloaded skipped=$skipped failed=$failed processed=$processed\n";
