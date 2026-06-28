#!/usr/bin/env php
<?php
use Magento\Framework\App\Bootstrap;
use Magento\Store\Model\ScopeInterface;

require __DIR__ . '/../../app/bootstrap.php';
$om = Bootstrap::create(BP, $_SERVER)->getObjectManager();

$state = $om->get(\Magento\Framework\App\State::class);
try { $state->setAreaCode('frontend'); } catch (\Exception $e) {}

$scopeConfig = $om->get(\Magento\Framework\App\Config\ScopeConfigInterface::class);
$storeManager = $om->get(\Magento\Store\Model\StoreManagerInterface::class);

foreach ([1, 2] as $storeId) {
    $storeManager->setCurrentStore($storeId);
    $code = $storeManager->getStore($storeId)->getCode();
    $tpl = (string)$scopeConfig->getValue(
        'ecomteck_storelocator/template/location_list_description',
        ScopeInterface::SCOPE_STORE,
        $storeId
    );
    echo "store=$code id=$storeId len=" . strlen($tpl) . "\n";
    if (preg_match('/google-map-direction.*?<span>([^<]+)<\\/span>/us', $tpl, $m)) {
        echo "  map span: {$m[1]}\n";
        echo "  hex: " . bin2hex($m[1]) . "\n";
    }
    echo "\n";
}
