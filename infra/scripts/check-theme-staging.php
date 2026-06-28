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

foreach (['en', 'ar'] as $code) {
    $store = $storeManager->getStore($code);
    echo "\n=== Store {$code} ===\n";
    echo "store_id={$store->getId()} locale={$store->getConfig('general/locale/code')} theme_id={$store->getConfig('design/theme/theme_id')}\n";
    $theme = $om->get(\Magento\Framework\View\DesignInterface::class);
    $theme->setDesignTheme($store->getId(), 'store');
    echo "resolved theme path: " . $theme->getDesignTheme()->getThemePath() . "\n";
}

echo "\n=== theme table ===\n";
foreach ($conn->fetchAll("SELECT theme_id, parent_id, theme_path, theme_title FROM theme ORDER BY theme_id") as $r) {
    echo "{$r['theme_id']} parent={$r['parent_id']} path={$r['theme_path']} title={$r['theme_title']}\n";
}
