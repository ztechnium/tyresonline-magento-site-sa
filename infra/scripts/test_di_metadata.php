<?php
$m = include '/var/www/magento/generated/metadata/frontend.php';
$key = 'Magento\\Checkout\\Model\\CompositeConfigProvider';
echo isset($m['arguments'][$key]) ? json_encode($m['arguments'][$key], JSON_PRETTY_PRINT) : 'NOT_FOUND';
echo PHP_EOL;
