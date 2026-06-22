<?php
$c = include '/var/www/magento/app/etc/config.php';
$payment = $c['system']['default']['payment'] ?? [];
foreach (['hyperpay', 'HyperPay_Visa', 'HyperPay_Mada', 'HyperPay_Master'] as $key) {
    echo "=== $key ===\n";
    print_r($payment[$key] ?? 'not set');
    echo "\n";
}
