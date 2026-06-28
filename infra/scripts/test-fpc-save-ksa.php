#!/usr/bin/env php
<?php
use Magento\Framework\App\Bootstrap;

require '/var/www/magento/app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();

$env = include '/var/www/magento/app/etc/env.php';
$redisHost = $env['cache']['frontend']['page_cache']['backend_options']['server'];
$redisDb = $env['cache']['frontend']['page_cache']['backend_options']['database'];

echo "Redis page_cache: $redisHost db=$redisDb\n";
echo 'DBSIZE before: ' . trim(shell_exec("redis-cli -h $redisHost -n $redisDb DBSIZE")) . "\n";

$cache = $om->get(\Magento\PageCache\Model\Cache\Type::class);
$key = 'fpc_test_' . time();
$cache->save('test-value', $key, ['FPC'], 60);
echo "save ok\n";
echo 'DBSIZE after test save: ' . trim(shell_exec("redis-cli -h $redisHost -n $redisDb DBSIZE")) . "\n";
echo 'test key exists: ' . ($cache->load($key) === 'test-value' ? 'yes' : 'no') . "\n";
$cache->remove($key);

// Simulate HTTP homepage
$_SERVER['HTTP_HOST'] = 'stg.tyresonline.sa';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = '/var/www/magento/pub/index.php';
$_SERVER['DOCUMENT_ROOT'] = '/var/www/magento/pub';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SERVER_PORT'] = '443';
$_SERVER['HTTPS'] = 'on';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

$bootstrap2 = Bootstrap::create(BP, $_SERVER);
$om2 = $bootstrap2->getObjectManager();
$state = $om2->get(\Magento\Framework\App\State::class);
try { $state->setAreaCode('frontend'); } catch (\Exception $e) {}

$request = $om2->get(\Magento\Framework\App\Request\Http::class);
$response = $om2->get(\Magento\Framework\App\Response\Http::class);
$front = $om2->get(\Magento\Framework\App\FrontController::class);

$response = $front->dispatch($request);
if ($response instanceof \Magento\Framework\App\Response\Http) {
    $cc = $response->getHeader('Cache-Control');
    echo 'Cache-Control: ' . ($cc ? $cc->getFieldValue() : 'none') . "\n";
    echo 'X-Magento-Tags: ' . ($response->getHeader('X-Magento-Tags') ? $response->getHeader('X-Magento-Tags')->getFieldValue() : 'none') . "\n";
    echo 'HTTP code: ' . $response->getHttpResponseCode() . "\n";
}

echo 'DBSIZE after dispatch: ' . trim(shell_exec("redis-cli -h $redisHost -n $redisDb DBSIZE")) . "\n";
echo 'sample keys: ' . trim(shell_exec("redis-cli -h $redisHost -n $redisDb KEYS '*' | head -3")) . "\n";
