#!/usr/bin/env php
<?php
declare(strict_types=1);
/**
 * Download blog thumbnails from alternate sources and upload to KSA S3 bucket.
 *
 * Usage (local, AWS creds in env):
 *   php sync-blog-media-to-s3.php [--limit=50] [--dry-run]
 *
 * Requires: aws cli, SSH access to KSA staging for DB paths (or run on server).
 */

$dryRun = in_array('--dry-run', $argv, true);
$limit = 0;
$bucket = getenv('S3_BUCKET') ?: 'tyresonline-sa-prod-media';
$mediaRoot = getenv('MAGENTO_ROOT') ?: '/var/www/magento';

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--limit=')) {
        $limit = max(1, (int) substr($arg, 8));
    }
}

$sourceBases = [
    'https://d3jcy1c5gdp13r.cloudfront.net/media/mgs_blog/',
    'https://d3cp9qx5vsaqph.cloudfront.net/media/mgs_blog/',
    'https://d1u7uj1o3a80t8.cloudfront.net/media/mgs_blog/',
    'https://www.tyresonline.ae/media/mgs_blog/',
    'https://stg.tyresonline.ae/media/mgs_blog/',
];

$envFile = $mediaRoot . '/app/etc/env.php';
if (!is_file($envFile)) {
    fwrite(STDERR, "env.php not found at {$envFile}\n");
    exit(1);
}

$env = include $envFile;
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
echo 'unique_blog_files=' . count($paths) . "\n";

$tmpdir = sys_get_temp_dir() . '/mgs_blog_sync';
if (!$dryRun && !is_dir($tmpdir) && !mkdir($tmpdir, 0775, true) && !is_dir($tmpdir)) {
    fwrite(STDERR, "Cannot create {$tmpdir}\n");
    exit(1);
}

$downloaded = 0;
$uploaded = 0;
$failed = 0;
$processed = 0;

foreach ($paths as $rel) {
    if ($limit > 0 && $processed >= $limit) {
        break;
    }
    $processed++;
    $s3Key = 'mgs_blog/' . $rel;
    $localDest = $tmpdir . '/' . $rel;

    if (!$dryRun) {
        $check = shell_exec('aws s3 ls ' . escapeshellarg('s3://' . $bucket . '/' . $s3Key) . ' 2>/dev/null');
        if (is_string($check) && trim($check) !== '') {
            continue;
        }
    }

    $bytes = null;
    foreach ($sourceBases as $base) {
        $url = $base . str_replace(' ', '%20', $rel);
        $bytes = httpGet($url);
        if ($bytes !== null) {
            break;
        }
    }

    if ($bytes === null && preg_match('/(\d{5,})\.(jpg|jpeg|png|webp)$/i', $rel, $m)) {
        $pexelsUrl = 'https://images.pexels.com/photos/' . $m[1] . '/pexels-photo-' . $m[1] . '.jpeg?auto=compress&cs=tinysrgb&w=1280';
        $bytes = httpGet($pexelsUrl);
    }

    if ($bytes === null) {
        $failed++;
        continue;
    }

    $downloaded++;
    if ($dryRun) {
        echo "would_upload s3://{$bucket}/{$s3Key} (" . strlen($bytes) . " bytes)\n";
        $uploaded++;
        continue;
    }

    $dir = dirname($localDest);
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        $failed++;
        continue;
    }
    file_put_contents($localDest, $bytes);

    $cmd = 'aws s3 cp ' . escapeshellarg($localDest) . ' ' . escapeshellarg('s3://' . $bucket . '/' . $s3Key) . ' --only-show-errors 2>&1';
    $out = shell_exec($cmd);
    if ($out !== null && trim($out) !== '') {
        $failed++;
        continue;
    }
    $uploaded++;
    if ($uploaded % 25 === 0) {
        echo "uploaded={$uploaded}\n";
    }
}

echo "downloaded={$downloaded} uploaded={$uploaded} failed={$failed} processed={$processed}\n";

function httpGet(string $url): ?string
{
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 20,
            'follow_location' => 1,
            'header' => "User-Agent: TyresOnline-KSA-MediaSync/1.0\r\n",
        ],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);
    $data = @file_get_contents($url, false, $ctx);
    if ($data === false || strlen($data) < 128) {
        return null;
    }
    if (str_starts_with($data, '<') || str_contains($data, 'Bad Gateway')) {
        return null;
    }
    return $data;
}
