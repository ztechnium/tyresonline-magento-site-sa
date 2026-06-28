#!/usr/bin/env php
<?php
require '/var/www/magento/app/bootstrap.php';
$_SERVER['HTTP_HOST'] = 'stg.tyresonline.sa';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTPS'] = 'on';

$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$state = $om->get(\Magento\Framework\App\State::class);
try { $state->setAreaCode('frontend'); } catch (\Exception $e) {}

$response = $om->get(\Magento\Framework\App\Response\Http::class);
$response->setHttpResponseCode(200);
$response->setPublicHeaders(86400);
$response->setHeader('X-Magento-Tags', 'store,FPC');
$response->setContent('<html>test</html>');

$cc = $response->getHeader('Cache-Control');
$ccVal = $cc ? $cc->getFieldValue() : 'none';
echo "Cache-Control before kernel: $ccVal\n";
echo 'preg_match: ' . (preg_match('/public.*s-maxage=(\d+)/', $ccVal, $m) ? 'yes maxAge='.$m[1] : 'no') . "\n";

$env = include '/var/www/magento/app/etc/env.php';
$rh = $env['cache']['frontend']['page_cache']['backend_options']['server'];
$rd = $env['cache']['frontend']['page_cache']['backend_options']['database'];
echo 'DBSIZE before: ' . trim(shell_exec("redis-cli -h $rh -n $rd DBSIZE")) . "\n";

$kernel = $om->get(\Magento\Framework\App\PageCache\Kernel::class);
$kernel->process($response);

echo 'DBSIZE after: ' . trim(shell_exec("redis-cli -h $rh -n $rd DBSIZE")) . "\n";
echo 'Keys: ' . trim(shell_exec("redis-cli -h $rh -n $rd KEYS '*' | tr '\n' ' '")) . "\n";
$cc2 = $response->getHeader('Cache-Control');
echo 'Cache-Control after kernel: ' . ($cc2 ? $cc2->getFieldValue() : 'none') . "\n";
