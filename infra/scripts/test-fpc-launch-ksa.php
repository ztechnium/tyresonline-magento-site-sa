#!/usr/bin/env php
<?php
// Quick check: does Result BuiltinPlugin path call kernel->process?
require '/var/www/magento/app/bootstrap.php';

$_SERVER['HTTP_HOST'] = 'stg.tyresonline.sa';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = '/var/www/magento/pub/index.php';
$_SERVER['DOCUMENT_ROOT'] = '/var/www/magento/pub';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SERVER_PORT'] = '443';
$_SERVER['HTTPS'] = 'on';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
/** @var \Magento\Framework\App\Http $app */
$app = $bootstrap->createApplication(\Magento\Framework\App\Http::class);

$env = include '/var/www/magento/app/etc/env.php';
$rh = $env['cache']['frontend']['page_cache']['backend_options']['server'];
$rd = $env['cache']['frontend']['page_cache']['backend_options']['database'];
echo 'DBSIZE before: ' . trim(shell_exec("redis-cli -h $rh -n $rd DBSIZE")) . "\n";

/** @var \Magento\Framework\App\Response\Http $httpResponse */
$httpResponse = $app->launch();
$cc = $httpResponse->getHeader('Cache-Control');
echo 'Final Cache-Control: ' . ($cc ? $cc->getFieldValue() : 'none') . "\n";
echo 'HTTP code: ' . $httpResponse->getHttpResponseCode() . "\n";
echo 'Content length: ' . strlen((string)$httpResponse->getContent()) . "\n";
$om = \Magento\Framework\App\ObjectManager::getInstance();
echo 'Registry use_page_cache_plugin: ' . ($om->get(\Magento\Framework\Registry::class)->registry('use_page_cache_plugin') ? 'true' : 'false') . "\n";
echo 'DBSIZE after: ' . trim(shell_exec("redis-cli -h $rh -n $rd DBSIZE")) . "\n";
echo 'Keys sample: ' . trim(shell_exec("redis-cli -h $rh -n $rd KEYS '*' | tr '\n' ' '")) . "\n";
