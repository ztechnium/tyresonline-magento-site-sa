#!/usr/bin/env php
<?php
use Magento\Framework\App\Bootstrap;

require '/var/www/magento/app/bootstrap.php';
$_SERVER['HTTP_HOST'] = 'stg.tyresonline.sa';
$_SERVER['REQUEST_URI'] = '/car-tyres';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTPS'] = 'on';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$state = $om->get(\Magento\Framework\App\State::class);
try { $state->setAreaCode('frontend'); } catch (\Exception $e) {}

$storeManager = $om->get(\Magento\Store\Model\StoreManagerInterface::class);
$storeManager->setCurrentStore('default');

$request = $om->get(\Magento\Framework\App\Request\Http::class);
$request->setRequestUri('/car-tyres');
$request->setPathInfo('/car-tyres');

$front = $om->get(\Magento\Framework\App\FrontController::class);
$response = $front->dispatch($request);

if ($response instanceof \Magento\Framework\Controller\ResultInterface) {
    $http = $om->get(\Magento\Framework\App\Response\Http::class);
    $result = $response->renderResult($http);
    $layout = $om->get(\Magento\Framework\View\LayoutInterface::class);
    echo 'isCacheable: ' . ($layout->isCacheable() ? 'yes' : 'no') . PHP_EOL;
    $xml = $layout->getXml();
    foreach ($xml->xpath('//block[@cacheable="false"]') ?: [] as $el) {
        $name = (string)$el->getAttribute('name');
        if ($name && $layout->getBlock($name)) {
            echo "active uncacheable block: $name (" . (string)$el->getAttribute('class') . ")\n";
        }
    }
    $cc = $http->getHeader('Cache-Control');
    echo 'Cache-Control: ' . ($cc ? $cc->getFieldValue() : 'none') . PHP_EOL;
}
