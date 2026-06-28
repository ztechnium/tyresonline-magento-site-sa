#!/usr/bin/env php
<?php
declare(strict_types=1);
$env = include '/var/www/magento/app/etc/env.php';
$db = $env['db']['connection']['default'];
$pdo = new PDO('mysql:host=' . $db['host'] . ';dbname=' . $db['dbname'], $db['username'], $db['password']);
$pdo->exec('INSERT IGNORE INTO cms_page_store (page_id, store_id) VALUES (11, 1)');
$pdo->exec("UPDATE url_rewrite SET target_path = 'cms/page/view/page_id/11', entity_id = 11, entity_type = 'cms-page' WHERE store_id = 1 AND request_path = 'promotions-and-offers-on-tyres-ksa' AND redirect_type = 0");
echo "fixed\n";
