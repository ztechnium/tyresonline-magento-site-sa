#!/usr/bin/env php
<?php
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$conn = $om->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();
$storeManager = $om->get(\Magento\Store\Model\StoreManagerInterface::class);

echo "=== Theme config ===\n";
foreach ($conn->fetchAll("SELECT scope, scope_id, path, value FROM core_config_data WHERE path LIKE 'design/theme/%' ORDER BY scope, scope_id") as $r) {
    echo "{$r['scope']} {$r['scope_id']} {$r['path']} = {$r['value']}\n";
}

$arStore = $storeManager->getStore('ar');
echo "\n=== AR store theme ===\n";
echo "store_id={$arStore->getId()} theme=" . $arStore->getConfig('design/theme/theme_id') . "\n";
$theme = $om->get(\Magento\Framework\View\DesignInterface::class);
$theme->setDesignTheme($arStore->getId(), 'store');
echo "resolved theme path: " . $theme->getDesignTheme()->getThemePath() . "\n";

echo "\n=== Brand list settings ===\n";
foreach ($conn->fetchAll("SELECT scope, scope_id, path, value FROM core_config_data WHERE path LIKE 'brand/list_page_settings/%'") as $r) {
    echo "{$r['scope']} {$r['scope_id']} {$r['path']} = {$r['value']}\n";
}
