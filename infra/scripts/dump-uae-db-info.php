#!/usr/bin/env php
<?php
$uaeEnv = include '/var/www/magento/app/etc/env.php';
$u = $uaeEnv['db']['connection']['default'];
echo $u['host'] . "\n" . $u['dbname'] . "\n";
