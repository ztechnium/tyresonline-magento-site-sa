#!/usr/bin/env php
<?php
declare(strict_types=1);
/**
 * Download missing mgs_blog files from UAE CloudFront CDN into local media.
 *
 * Usage: sudo -u www-data php download-blog-media-from-cdn.php [--limit=50] [--dry-run]
 */

$cdnBase = getenv('UAE_BLOG_CDN_BASE') ?: 'https://d1u7uj1o3a80t8.cloudfront.net/media/mgs_blog/';
$mediaRoot = '/var/www/magento/pub/media/mgs_blog';
$dryRun = in_array('--dry-run', $argv, true);
$limit = 0;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--limit=')) {
        $limit = max(1, (int) substr($arg, 8));
    }
}

$env = include '/var/www/magento/app/etc/env.php';
$db = $env['db']['connection']['default'];
$pdo = new PDO(
    'mysql:host=' . $db['host'] . ';dbname=' . $db['dbname'],
    $db['username'],
    $db['password']
);

$paths = [];
foreach ($pdo->query('SELECT thumbnail, image FROM mgs_blog_post') as $row) {
    foreach (['thumbnail', 'image'] as $field) {
        $file = trim((string) ($row[$field] ?? ''));
        if ($file === '' || $file === 'no_image.png') {
            continue;
        }
        $file = preg_replace('#^mgs_blog/#', '', $file);
        $paths[$file] = true;
    }
}

$paths = array_keys($paths);
sort($paths);
echo 'unique_files=' . count($paths) . "\n";

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

    $url = rtrim($cdnBase, '/') . '/' . str_replace(' ', '%20', $rel);
    if ($dryRun) {
        echo "would_download $url\n";
        $downloaded++;
        continue;
    }

    $dir = dirname($dest);
    if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
        $failed++;
        continue;
    }

    $ctx = stream_context_create([
        'http' => ['timeout' => 20, 'follow_location' => 1, 'header' => "User-Agent: TyresOnline-KSA-MediaSync/1.0\r\n"],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);
    $data = @file_get_contents($url, false, $ctx);
    if ($data === false || strlen($data) < 128) {
        $failed++;
        continue;
    }

    if (@file_put_contents($dest, $data) === false) {
        $failed++;
        continue;
    }

    @chmod($dest, 0664);
    $downloaded++;
    if ($downloaded % 25 === 0) {
        echo "downloaded=$downloaded\n";
    }
}

echo "downloaded=$downloaded skipped=$skipped failed=$failed processed=$processed\n";
