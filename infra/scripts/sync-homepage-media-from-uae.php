#!/usr/bin/env php
<?php
/**
 * Sync homepage banner + blog media from UAE staging to KSA.
 * Run on KSA EC2: php /tmp/sync-homepage-media-from-uae.php
 */
declare(strict_types=1);

$mediaRoot = '/var/www/magento/pub/media';
$sourceBase = 'https://stg.tyresonline.ae/media/';
$env = include '/var/www/magento/app/etc/env.php';
$db = $env['db']['connection']['default'];
$pdo = new PDO(
    'mysql:host=' . $db['host'] . ';dbname=' . $db['dbname'],
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

function ensureDir(string $path): void
{
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

function downloadFile(string $url, string $dest): bool
{
    if (is_file($dest) && filesize($dest) > 100) {
        return true;
    }
    ensureDir(dirname($dest));
    $ctx = stream_context_create([
        'http' => ['timeout' => 30, 'user_agent' => 'KSA-Media-Sync/1.0'],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);
    $data = @file_get_contents($url, false, $ctx);
    if ($data === false || strlen($data) < 50) {
        echo "FAIL $url\n";
        return false;
    }
    file_put_contents($dest, $data);
    echo "OK   $dest\n";
    return true;
}

$paths = [];

foreach ($pdo->query('SELECT image, image_ar, banner_image_two, banner_image_two_ar, banner_image_three, banner_image_three_ar, brand_logo FROM mageplaza_bannerslider_banner') as $row) {
    foreach ($row as $file) {
        $file = trim((string) $file);
        if ($file !== '') {
            $paths['mageplaza/bannerslider/banner/image/' . ltrim($file, '/')] = true;
        }
    }
}

foreach ($pdo->query('SELECT thumbnail, image FROM mgs_blog_post') as $row) {
    foreach ($row as $file) {
        $file = trim((string) $file);
        if ($file !== '' && $file !== 'no_image.png') {
            $paths['mgs_blog/' . preg_replace('#^mgs_blog/#', '', $file)] = true;
        }
    }
}

$paths['images/logo/Logo-white.svg'] = true;
$paths['images/logo/Logo-black.svg'] = true;

$ok = 0;
$fail = 0;
foreach (array_keys($paths) as $relative) {
    $dest = rtrim($mediaRoot, '/') . '/' . $relative;
    if (downloadFile($sourceBase . $relative, $dest)) {
        $ok++;
    } else {
        $fail++;
    }
}

// Theme static fallbacks for logo if media copy failed
$themeLogo = '/var/www/magento/app/design/frontend/Hditsol/tyresonline/web/images/logo/Logo-white.svg';
$mediaLogo = $mediaRoot . '/images/logo/Logo-white.svg';
if (!is_file($mediaLogo) && is_file($themeLogo)) {
    ensureDir(dirname($mediaLogo));
    copy($themeLogo, $mediaLogo);
    echo "OK   copied theme Logo-white.svg to media\n";
    $ok++;
}

echo "Done: $ok downloaded/existing, $fail failed\n";
