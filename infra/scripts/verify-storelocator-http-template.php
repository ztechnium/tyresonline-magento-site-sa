#!/usr/bin/env php
<?php
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Request\Http;
use Magento\Store\Model\ScopeInterface;

require __DIR__ . '/../../app/bootstrap.php';
$om = Bootstrap::create(BP, $_SERVER)->getObjectManager();

$state = $om->get(\Magento\Framework\App\State::class);
try { $state->setAreaCode('frontend'); } catch (\Exception $e) {}

$storeManager = $om->get(\Magento\Store\Model\StoreManagerInterface::class);
$storeManager->setCurrentStore(2);

$scopeConfig = $om->get(\Magento\Framework\App\Config\ScopeConfigInterface::class);
$scopeTpl = (string)$scopeConfig->getValue(
    'ecomteck_storelocator/template/location_list_description',
    ScopeInterface::SCOPE_STORE,
    2
);

/** @var \Ecomteck\StoreLocator\Controller\Js\Template $controller */
$controller = $om->create(\Ecomteck\StoreLocator\Controller\Js\Template::class);
$request = $om->create(Http::class);
$request->setParams(['template' => 'location_list_description']);

$ref = new ReflectionClass($controller);
$method = $ref->getMethod('getTemplate');
$method->setAccessible(true);
$controllerTpl = (string)$method->invoke($controller, 'location_list_description');

foreach (['scopeConfig' => $scopeTpl, 'controller' => $controllerTpl] as $label => $tpl) {
    if (preg_match('/google-map-direction.*?<span>([^<]+)<\\/span>/us', $tpl, $m)) {
        echo "{$label} map: {$m[1]}\n";
        echo "{$label} mojibake: " . (preg_match('/[\x{2500}-\x{25FF}]/u', $tpl) ? 'YES' : 'NO') . "\n";
    }
}
