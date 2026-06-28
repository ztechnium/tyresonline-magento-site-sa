<?php
declare(strict_types=1);
$env = include '/var/www/magento/app/etc/env.php';
$db = $env['db']['connection']['default'];
$pdo = new PDO('mysql:host='.$db['host'].';dbname='.$db['dbname'], $db['username'], $db['password']);
$rows = $pdo->query('SELECT brand_id, name, image FROM mgs_brand WHERE image IS NOT NULL AND image != "" LIMIT 10')->fetchAll(PDO::FETCH_ASSOC);
echo 'brands_with_image=' . $pdo->query('SELECT COUNT(*) FROM mgs_brand WHERE image IS NOT NULL AND image != ""')->fetchColumn() . "\n";
foreach ($rows as $r) echo json_encode($r) . "\n";
