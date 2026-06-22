<?php
$global = include '/var/www/magento/generated/metadata/global.php';
$frontend = include '/var/www/magento/generated/metadata/frontend.php';
$iface = 'Magento\\Checkout\\CustomerData\\ItemPoolInterface';
echo 'global_pref=' . ($global['preferences'][$iface] ?? 'NONE') . PHP_EOL;
echo 'frontend_pref=' . ($frontend['preferences'][$iface] ?? 'NONE') . PHP_EOL;
echo 'global_has_frontend_prefs=' . (isset($global['preferences']) ? count($global['preferences']) : 0) . PHP_EOL;
echo 'frontend_has_prefs=' . (isset($frontend['preferences']) ? count($frontend['preferences']) : 0) . PHP_EOL;

$captcha = 'Magento\\Captcha\\Model\\Checkout\\ConfigProvider';
echo 'frontend_captcha_formIds=' . json_encode($frontend['arguments'][$captcha]['formIds'] ?? null) . PHP_EOL;
echo 'global_captcha_formIds=' . json_encode($global['arguments'][$captcha]['formIds'] ?? null) . PHP_EOL;
