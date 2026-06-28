#!/usr/bin/env php
<?php
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../../app/bootstrap.php';
$om = Bootstrap::create(BP, $_SERVER)->getObjectManager();
$state = $om->get(\Magento\Framework\App\State::class);
try { $state->setAreaCode('frontend'); } catch (\Exception $e) {}
$om->get(\Magento\Store\Model\StoreManagerInterface::class)->setCurrentStore(2);
$scope = $om->get(\Magento\Framework\App\Config\ScopeConfigInterface::class);
echo 'title=' . $scope->getValue('blog/general_settings/title') . "\n";
echo 'meta_title=' . $scope->getValue('blog/general_settings/meta_title') . "\n";
echo 'meta_keywords=' . $scope->getValue('blog/general_settings/meta_keywords') . "\n";

$conn = $om->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();
foreach ($conn->fetchAll("SELECT scope, scope_id, path, value FROM core_config_data WHERE path LIKE 'blog/general_settings/%' ORDER BY path, scope, scope_id") as $r) {
    echo "{$r['scope']}:{$r['scope_id']} {$r['path']} = {$r['value']}\n";
}
