<?php
$env = include '/var/www/magento/app/etc/env.php';
$db = $env['db']['connection']['default'];
$pdo = new PDO('mysql:host='.$db['host'].';dbname='.$db['dbname'], $db['username'], $db['password']);
$rows = $pdo->query("SELECT scope, scope_id, path, value FROM core_config_data WHERE path LIKE 'web/%base%' OR path LIKE 'general/locale/%' OR path LIKE 'design/%' ORDER BY path, scope_id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo "scope={$r['scope']}:{$r['scope_id']} {$r['path']}={$r['value']}\n";
}
echo "---stores---\n";
foreach ($pdo->query('SELECT store_id, code, name, is_active FROM store') as $s) {
    echo implode(' ', $s) . "\n";
}
