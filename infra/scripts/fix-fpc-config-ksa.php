#!/usr/bin/env php
<?php
$env = include '/var/www/magento/app/etc/env.php';
$db = $env['db']['connection']['default'];
$pdo = new PDO("mysql:host={$db['host']};dbname={$db['dbname']}", $db['username'], $db['password']);

$updates = [
    'system/full_page_cache/caching_application' => '1',
    'mgt_varnish/module/is_enabled' => '0',
    'mgt_varnish/module/debug_mode' => '0',
];
foreach ($updates as $path => $value) {
    $stmt = $pdo->prepare('UPDATE core_config_data SET value = ? WHERE path = ? AND scope = ? AND scope_id = ?');
    $stmt->execute([$value, $path, 'default', 0]);
    echo "Updated $path => $value (rows: {$stmt->rowCount()})\n";
}

echo "Done. Run: php bin/magento cache:clean config full_page\n";
