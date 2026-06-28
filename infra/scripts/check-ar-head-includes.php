<?php
$env = include '/var/www/magento/app/etc/env.php';
$db = $env['db']['connection']['default'];
$pdo = new PDO('mysql:host='.$db['host'].';dbname='.$db['dbname'], $db['username'], $db['password']);
$val = $pdo->query("SELECT value FROM core_config_data WHERE path='design/head/includes' AND scope='stores' AND scope_id=2")->fetchColumn();
file_put_contents('/tmp/ar_head_includes_raw.html', (string)$val);
echo 'len=' . strlen((string)$val) . "\n";
echo 'style_open=' . substr_count((string)$val, '<style') . ' style_close=' . substr_count((string)$val, '</style>') . "\n";
echo "tail:\n" . substr((string)$val, -600) . "\n";
