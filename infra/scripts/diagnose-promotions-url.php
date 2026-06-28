#!/usr/bin/env php
<?php
declare(strict_types=1);
$env = include '/var/www/magento/app/etc/env.php';
$db = $env['db']['connection']['default'];
$pdo = new PDO('mysql:host=' . $db['host'] . ';dbname=' . $db['dbname'], $db['username'], $db['password']);

echo "=== stores ===\n";
foreach ($pdo->query('SELECT store_id, code, name FROM store') as $r) {
    echo "store_id={$r['store_id']} code={$r['code']} name={$r['name']}\n";
}

$slug = 'promotions-and-offers-on-tyres-ksa';
echo "\n=== cms_page ===\n";
foreach ($pdo->query("SELECT page_id, identifier, title, is_active FROM cms_page WHERE identifier LIKE '%promotions%'") as $r) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
}

$pageId = $pdo->query("SELECT page_id FROM cms_page WHERE identifier = '$slug' LIMIT 1")->fetchColumn();
echo "\npage_id=$pageId\n";

echo "\n=== cms_page_store all ===\n";
foreach ($pdo->query('SELECT * FROM cms_page_store WHERE page_id IN (11,37)') as $r) {
    echo json_encode($r) . "\n";
}

echo "\n=== url_rewrite for slug ===\n";
foreach ($pdo->query("SELECT url_rewrite_id, store_id, request_path, target_path, redirect_type FROM url_rewrite WHERE request_path LIKE '%promotions-and-offers%' OR target_path LIKE '%page_id/$pageId%'") as $r) {
    echo json_encode($r) . "\n";
}
