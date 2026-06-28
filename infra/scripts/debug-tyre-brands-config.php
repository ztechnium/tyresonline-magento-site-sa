#!/usr/bin/env php
<?php
use Magento\Framework\App\Bootstrap;
use Magento\Store\Model\ScopeInterface;

require __DIR__ . '/../../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$state = $om->get(\Magento\Framework\App\State::class);
try { $state->setAreaCode('frontend'); } catch (\Exception $e) {}

$scopeConfig = $om->get(\Magento\Framework\App\Config\ScopeConfigInterface::class);
$storeManager = $om->get(\Magento\Store\Model\StoreManagerInterface::class);

foreach ([1 => 'en', 2 => 'ar'] as $storeId => $code) {
    $store = $storeManager->getStore($storeId);
    echo "=== store {$code} ({$storeId}) ===\n";
    foreach (['brand/list_page_settings/title', 'mgs_brand/list_page_settings/title'] as $path) {
        $v = $scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
        echo "  {$path}: " . substr((string)$v, 0, 120) . "\n";
    }
    $helper = $om->get(\MGS\Brand\Helper\Data::class);
    echo "  helper title: " . substr((string)$helper->getConfig('list_page_settings/title', $storeId), 0, 120) . "\n";
}

$conn = $om->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();
echo "\n=== DB paths containing list_page_settings/title ===\n";
foreach ($conn->fetchAll("SELECT scope, scope_id, path, LEFT(value,120) v FROM core_config_data WHERE path LIKE '%list_page_settings/title%'") as $r) {
    echo "{$r['scope']} {$r['scope_id']} {$r['path']}: {$r['v']}\n";
}
