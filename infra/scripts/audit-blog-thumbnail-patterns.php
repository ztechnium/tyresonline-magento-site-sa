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
    'SELECT thumbnail FROM mgs_blog_post WHERE thumbnail != "" AND thumbnail != "no_image.png"'
)->fetchAll(PDO::FETCH_COLUMN);

$pexels = 0;
$other = 0;
foreach ($rows as $thumb) {
    if (preg_match('/(\d{5,})\.(jpg|jpeg|png|webp)$/i', (string) $thumb)) {
        $pexels++;
    } else {
        $other++;
    }
}
echo 'total=' . count($rows) . " pexels_pattern=$pexels other=$other\n";
