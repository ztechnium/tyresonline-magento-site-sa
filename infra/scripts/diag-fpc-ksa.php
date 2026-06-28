#!/usr/bin/env php
<?php
use Magento\Framework\App\Bootstrap;

require '/var/www/magento/app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();

$license = $om->get(\Mgt\Varnish\Model\License::class);
$vc = $om->get(\Mgt\Varnish\Model\Cache\Config::class);
$pc = $om->get(\Magento\PageCache\Model\Config::class);

$hosts = ['stg.tyresonline.sa', 'www.tyresonline.sa', 'tyresonline.sa'];
echo "=== Mgt Varnish ===\n";
echo 'isEnabled: ' . ($vc->isEnabled() ? 'yes' : 'no') . "\n";
echo 'debug_mode: ' . ($vc->isDebugModeEnabled() ? 'yes' : 'no') . "\n";
echo 'default_cache_lifetime: ' . $vc->getDefaultCacheLifetime() . "\n";
foreach ($hosts as $h) {
    echo "license[$h]: " . ($license->hasLicense($h) ? 'yes' : 'no') . "\n";
}

echo "\n=== PageCache ===\n";
echo 'FPC enabled: ' . ($pc->isEnabled() ? 'yes' : 'no') . "\n";
echo 'FPC type: ' . $pc->getType() . " (1=built-in, 2=varnish)\n";
echo 'TTL: ' . $pc->getTtl() . "\n";

$moduleManager = $om->get(\Magento\Framework\Module\Manager::class);
echo "\n=== Modules ===\n";
foreach (['Mgt_DeveloperToolbar', 'Mgt_Varnish', 'Magento_PageCache'] as $mod) {
    echo "$mod: " . ($moduleManager->isEnabled($mod) ? 'enabled' : 'disabled') . "\n";
}

$pdo = new PDO(
    'mysql:host=' . (include '/var/www/magento/app/etc/env.php')['db']['connection']['default']['host'] .
    ';dbname=' . (include '/var/www/magento/app/etc/env.php')['db']['connection']['default']['dbname'],
    (include '/var/www/magento/app/etc/env.php')['db']['connection']['default']['username'],
    (include '/var/www/magento/app/etc/env.php')['db']['connection']['default']['password']
);
$rows = $pdo->query("SELECT path, value FROM core_config_data WHERE path LIKE 'mgt_varnish/%' OR path LIKE 'system/full_page_cache/%' OR path LIKE 'dev/%' ORDER BY path")->fetchAll(PDO::FETCH_ASSOC);
echo "\n=== Relevant config ===\n";
foreach ($rows as $r) {
    echo $r['path'] . ' = ' . substr($r['value'], 0, 80) . "\n";
}
