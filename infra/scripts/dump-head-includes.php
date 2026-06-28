<?php
$env = include '/var/www/magento/app/etc/env.php';
$db = $env['db']['connection']['default'];
$pdo = new PDO('mysql:host='.$db['host'].';dbname='.$db['dbname'], $db['username'], $db['password']);
foreach ([1 => 'en', 2 => 'ar'] as $sid => $label) {
    $stmt = $pdo->prepare("SELECT value FROM core_config_data WHERE path='design/head/includes' AND scope='stores' AND scope_id=?");
    $stmt->execute([$sid]);
    $val = $stmt->fetchColumn();
    echo "=== store $sid ($label) head includes len=" . strlen((string)$val) . " ===\n";
    echo substr((string)$val, 0, 800) . "\n\n";
}
