#!/usr/bin/env php
<?php
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Request\Http;

require __DIR__ . '/../../app/bootstrap.php';
$om = Bootstrap::create(BP, $_SERVER)->getObjectManager();

$state = $om->get(\Magento\Framework\App\State::class);
try { $state->setAreaCode('frontend'); } catch (\Exception $e) {}

$storeManager = $om->get(\Magento\Store\Model\StoreManagerInterface::class);
$storeManager->setCurrentStore(2);

try {
    /** @var \Hdweb\Tyrefinder\Controller\Ajax\Gettyresize $controller */
    $controller = $om->create(\Hdweb\Tyrefinder\Controller\Ajax\Gettyresize::class);
    $request = $om->get(\Magento\Framework\App\RequestInterface::class);
    $request->setParams(['format' => 'json']);
    $result = $controller->execute();
    if ($result instanceof \Magento\Framework\Controller\Result\Json) {
        $data = json_decode(json_encode($result->renderResult($om->get(\Magento\Framework\App\Response\Http::class))), true);
    }
    echo "OK\n";
    echo 'options length: ' . strlen((string)($data['response'] ?? '')) . "\n";
} catch (\Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
    echo $e->getFile() . ':' . $e->getLine() . "\n";
}
