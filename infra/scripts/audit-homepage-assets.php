#!/usr/bin/env php
<?php
require '/var/www/magento/app/bootstrap.php';
$c = Magento\Framework\App\Bootstrap::create(BP, $_SERVER)->getObjectManager()
    ->get(Magento\Framework\App\ResourceConnection::class)->getConnection();

echo "=== active banners ===\n";
foreach ($c->fetchAll(
    "SELECT banner_id, title, image, banner_image_two, brand_logo FROM mageplaza_bannerslider_banner WHERE status=1 ORDER BY sort_order LIMIT 10"
) as $r) {
    echo json_encode($r) . "\n";
}

echo "=== blog posts on homepage ===\n";
foreach ($c->fetchAll(
    "SELECT post_id, title, thumbnail, image FROM mgs_blog_post WHERE status=1 ORDER BY created_at DESC LIMIT 6"
) as $r) {
    echo json_encode($r) . "\n";
}

$media = '/var/www/magento/pub/media/';
$checks = [
    'mageplaza/bannerslider/banner/image',
    'images/logo/Logo-white.svg',
    'images/logo/Logo.svg',
    'mgs_blog',
];
echo "=== media dirs ===\n";
foreach ($checks as $path) {
    $full = $media . $path;
    if (is_dir($full)) {
        $count = count(glob($full . '/*'));
        echo "$path: dir ($count files)\n";
    } elseif (is_file($full)) {
        echo "$path: file OK\n";
    } else {
        echo "$path: MISSING\n";
    }
}
