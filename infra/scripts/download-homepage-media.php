#!/usr/bin/env php
<?php
declare(strict_types=1);
/**
 * Download homepage banner + blog media from alternate CDN/origin sources.
 * Run on KSA: sudo php /tmp/download-homepage-media.php
 */

$mediaRoot = '/var/www/magento/pub/media';
$sourceBases = [
    'https://d3jcy1c5gdp13r.cloudfront.net/media/',
    'https://d3cp9qx5vsaqph.cloudfront.net/media/',
    'https://d1u7uj1o3a80t8.cloudfront.net/media/',
    'https://www.tyresonline.ae/media/',
    'https://stg.tyresonline.ae/media/',
];

$env = include '/var/www/magento/app/etc/env.php';
$db = $env['db']['connection']['default'];
$pdo = new PDO(
    'mysql:host=' . $db['host'] . ';dbname=' . $db['dbname'],
    $db['username'],
    $db['password']
);

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

function ensureDir(string $path): bool
{
    return is_dir($path) || mkdir($path, 0775, true);
}

function downloadFromSources(array $bases, string $relative, string $dest): bool
{
    if (is_file($dest) && filesize($dest) > 128) {
        return true;
    }
    ensureDir(dirname($dest));
    foreach ($bases as $base) {
        $url = rtrim($base, '/') . '/' . str_replace(' ', '%20', $relative);
        $ctx = stream_context_create([
            'http' => ['timeout' => 25, 'follow_location' => 1, 'header' => "User-Agent: TyresOnline-KSA-MediaSync/1.0\r\n"],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);
        $data = @file_get_contents($url, false, $ctx);
        if ($data !== false && strlen($data) > 128) {
            file_put_contents($dest, $data);
            chmod($dest, 0664);
            echo "OK   $relative (from $base)\n";
            return true;
        }
    }
    echo "FAIL $relative\n";
    return false;
}

$ok = 0;
$fail = 0;
foreach (array_keys($paths) as $relative) {
    $dest = $mediaRoot . '/' . $relative;
    if (downloadFromSources($sourceBases, $relative, $dest)) {
        $ok++;
    } else {
        $fail++;
    }
}

$themeLogo = '/var/www/magento/app/design/frontend/Hditsol/tyresonline/web/images/logo/Logo-white.svg';
$mediaLogo = $mediaRoot . '/images/logo/Logo-white.svg';
if (!is_file($mediaLogo) && is_file($themeLogo)) {
    ensureDir(dirname($mediaLogo));
    copy($themeLogo, $mediaLogo);
    echo "OK   copied Logo-white.svg from theme\n";
    $ok++;
}

echo "Done: $ok ok, $fail failed\n";
