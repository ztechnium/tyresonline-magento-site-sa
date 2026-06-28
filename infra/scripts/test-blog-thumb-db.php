#!/usr/bin/env php
<?php
declare(strict_types=1);
$env = include '/var/www/magento/app/etc/env.php';
$db = $env['db']['connection']['default'];
$pdo = new PDO('mysql:host=' . $db['host'] . ';dbname=' . $db['dbname'], $db['username'], $db['password']);
$row = $pdo->query("SELECT post_id, title, thumbnail FROM mgs_blog_post WHERE title LIKE '%تأمين%' LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
print_r($row);
