#!/usr/bin/env php
<?php
require '/var/www/magento/app/bootstrap.php';
$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$state = $om->get(\Magento\Framework\App\State::class);
try { $state->setAreaCode('frontend'); } catch (\Exception $e) {}
$pc = $om->get(\Magento\PageCache\Model\Config::class);
$mgt = $om->get(\Mgt\Varnish\Model\Cache\Config::class);
echo 'frontend PageCache type=' . $pc->getType() . ' (1=built-in, 2=varnish)' . PHP_EOL;
echo 'mgt varnish enabled=' . ($mgt->isEnabled() ? 'yes' : 'no') . PHP_EOL;

$env = include '/var/www/magento/app/etc/env.php';
$db = $env['db']['connection']['default'];
$pdo = new PDO("mysql:host={$db['host']};dbname={$db['dbname']}", $db['username'], $db['password']);
$rows = $pdo->query("SELECT scope, scope_id, path, value FROM core_config_data WHERE path IN ('system/full_page_cache/caching_application','mgt_varnish/module/is_enabled') ORDER BY path, scope, scope_id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo "{$r['scope']}/{$r['scope_id']} {$r['path']} = {$r['value']}\n";
}
