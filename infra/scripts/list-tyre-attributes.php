<?php
declare(strict_types=1);
$env = include '/var/www/magento/app/etc/env.php';
$db = $env['db']['connection']['default'];
$pdo = new PDO('mysql:host='.$db['host'].';dbname='.$db['dbname'], $db['username'], $db['password']);
$codes = $pdo->query("SELECT attribute_code, frontend_label FROM eav_attribute WHERE entity_type_id=4 AND (attribute_code LIKE '%tyre%' OR attribute_code LIKE '%width%' OR attribute_code LIKE '%height%' OR attribute_code LIKE '%rim%' OR attribute_code LIKE '%brand%' OR attribute_code LIKE '%pattern%' OR attribute_code LIKE '%model%' OR attribute_code LIKE '%name%') ORDER BY attribute_code")->fetchAll(PDO::FETCH_ASSOC);
foreach ($codes as $c) {
    echo $c['attribute_code'] . "\t" . $c['frontend_label'] . "\n";
}
