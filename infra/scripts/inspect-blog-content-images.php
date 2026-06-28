#!/usr/bin/env php
<?php
declare(strict_types=1);

$env = include '/var/www/magento/app/etc/env.php';
$db = $env['db']['connection']['default'];
$pdo = new PDO(
    'mysql:host=' . $db['host'] . ';dbname=' . $db['dbname'],
    $db['username'],
    $db['password']
);

$rows = $pdo->query(
    'SELECT post_id, title, thumbnail, image, LENGTH(content) clen
     FROM mgs_blog_post
     WHERE thumbnail != "" AND thumbnail != "no_image.png"
     ORDER BY published_at DESC LIMIT 12'
)->fetchAll(PDO::FETCH_ASSOC);

$withImgInContent = 0;
foreach ($rows as $r) {
    $content = (string) $pdo->query(
        'SELECT content FROM mgs_blog_post WHERE post_id=' . (int) $r['post_id']
    )->fetchColumn();
    $src = null;
    if (preg_match('/src=["\']([^"\']+)["\']/', $content, $m)) {
        $src = $m[1];
        $withImgInContent++;
    }
    echo "post={$r['post_id']} thumb={$r['thumbnail']} src=" . ($src ?: 'none') . "\n";
}
echo "posts_with_content_img=$withImgInContent/" . count($rows) . "\n";
