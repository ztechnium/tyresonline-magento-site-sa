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

/** @var \Ecomteck\StoreLocator\Controller\Js\Template $controller */
$request = $om->create(Http::class);
$request->setParams(['template' => 'location_list_description']);

$response = $om->create(\Magento\Framework\App\Response\Http::class);
$scopeConfig = $om->get(\Magento\Framework\App\Config\ScopeConfigInterface::class);

$template = (string)$scopeConfig->getValue(
    'ecomteck_storelocator/template/location_list_description',
    ScopeInterface::SCOPE_STORE,
    2
);

if (preg_match('/google-map-direction.*?<span>([^<]+)<\\/span>/us', $template, $m)) {
    echo "map: {$m[1]}\n";
}
if (preg_match('/submitstore.*?<span>([^<]+)<\\/span>/us', $template, $m)) {
    echo "button: {$m[1]}\n";
}
if (preg_match('/checkboxInstaller.*?<label[^>]*>([^<]+)<\\/label>/us', $template, $m)) {
    echo "label: " . trim($m[1]) . "\n";
}
echo 'mojibake: ' . (preg_match('/[\x{2500}-\x{25FF}]/u', $template) ? 'YES' : 'NO') . "\n";
