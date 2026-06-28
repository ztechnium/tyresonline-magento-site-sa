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

$row = $pdo->query(
    'SELECT post_id, title, thumbnail, image, LEFT(content, 2000) AS content_snip
     FROM mgs_blog_post ORDER BY post_id DESC LIMIT 5'
)->fetchAll(PDO::FETCH_ASSOC);

foreach ($row as $r) {
    echo "post={$r['post_id']} thumb={$r['thumbnail']}\n";
    echo "title={$r['title']}\n";
    if (preg_match_all('/src=["\']([^"\']+)["\']/', (string) $r['content_snip'], $matches)) {
        foreach (array_slice($matches[1], 0, 3) as $src) {
            echo "content_img={$src}\n";
        }
    } else {
        echo "content_img=none\n";
    }
    echo "---\n";
}
