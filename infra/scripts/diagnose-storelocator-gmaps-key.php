#!/usr/bin/env php
<?php
require __DIR__ . '/../../app/bootstrap.php';
$om = Magento\Framework\App\Bootstrap::create(BP, $_SERVER)->getObjectManager();
$scope = $om->get(Magento\Framework\App\Config\ScopeConfigInterface::class);
foreach ([['default', 0], ['stores', 1], ['stores', 2]] as [$scopeCode, $scopeId]) {
    $key = (string)$scope->getValue('ecomteck_storelocator/map/api_key', $scopeCode, $scopeId);
    echo "$scopeCode/$scopeId api_key len=" . strlen($key) . " prefix=" . substr($key, 0, 10) . "\n";
}
