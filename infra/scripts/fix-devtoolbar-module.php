#!/usr/bin/env php
<?php
$env = include '/var/www/magento/app/etc/env.php';
$db = $env['db']['connection']['default'];
$pdo = new PDO("mysql:host={$db['host']};dbname={$db['dbname']}", $db['username'], $db['password']);
$pdo->exec("INSERT IGNORE INTO setup_module (module,schema_version,data_version) VALUES ('Mgt_DeveloperToolbar','1.0.0','1.0.0')");
echo "Mgt_DeveloperToolbar registered\n";
