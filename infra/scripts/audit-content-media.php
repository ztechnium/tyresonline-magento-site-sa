#!/usr/bin/env php
<?php
declare(strict_types=1);
/**
 * Audit missing blog and banner media files on disk.
 *
 * Usage: sudo -u www-data php audit-content-media.php [--limit=20]
 */

$limit = 20;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--limit=')) {
        $limit = max(1, (int) substr($arg, 8));
    }
}

$mediaRoot = '/var/www/magento/pub/media';
$env = include '/var/www/magento/app/etc/env.php';
$db = $env['db']['connection']['default'];
$pdo = new PDO(
    'mysql:host=' . $db['host'] . ';dbname=' . $db['dbname'],
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

function mediaExists(string $mediaRoot, string $relative): bool
{
    $relative = ltrim(str_replace('\\', '/', $relative), '/');
    if ($relative === '') {
        return false;
    }
    return is_file(rtrim($mediaRoot, '/') . '/' . $relative);
}

$brokenBlog = [];
$blogRows = $pdo->query('SELECT post_id, title, thumbnail, image FROM mgs_blog_post')->fetchAll(PDO::FETCH_ASSOC);
foreach ($blogRows as $row) {
    foreach (['thumbnail', 'image'] as $field) {
        $file = trim((string) ($row[$field] ?? ''));
        if ($file === '' || $file === 'no_image.png') {
            continue;
        }
        $relative = 'mgs_blog/' . preg_replace('#^mgs_blog/#', '', $file);
        if (!mediaExists($mediaRoot, $relative)) {
            $brokenBlog[] = ['id' => $row['post_id'], 'title' => $row['title'], 'field' => $field, 'path' => $relative];
        }
    }
}

$brokenBanners = [];
$bannerRows = $pdo->query(
    'SELECT banner_id, name, image, image_ar, banner_image_two, banner_image_two_ar, banner_image_three, banner_image_three_ar, brand_logo
     FROM mageplaza_bannerslider_banner'
)->fetchAll(PDO::FETCH_ASSOC);
$bannerFields = [
    'image', 'image_ar', 'banner_image_two', 'banner_image_two_ar',
    'banner_image_three', 'banner_image_three_ar', 'brand_logo',
];
foreach ($bannerRows as $row) {
    foreach ($bannerFields as $field) {
        $file = trim((string) ($row[$field] ?? ''));
        if ($file === '') {
            continue;
        }
        $relative = 'mageplaza/bannerslider/banner/image/' . ltrim($file, '/');
        if (!mediaExists($mediaRoot, $relative)) {
            $brokenBanners[] = ['id' => $row['banner_id'], 'name' => $row['name'], 'field' => $field, 'path' => $relative];
        }
    }
}

echo 'blog_posts=' . count($blogRows) . "\n";
echo 'broken_blog_files=' . count($brokenBlog) . "\n";
echo 'banners=' . count($bannerRows) . "\n";
echo 'broken_banner_files=' . count($brokenBanners) . "\n";
echo "sample_broken_blog:\n";
foreach (array_slice($brokenBlog, 0, $limit) as $row) {
    echo "  post={$row['id']} {$row['field']} {$row['path']} title={$row['title']}\n";
}
echo "sample_broken_banners:\n";
foreach (array_slice($brokenBanners, 0, $limit) as $row) {
    echo "  banner={$row['id']} {$row['field']} {$row['path']} name={$row['name']}\n";
}
