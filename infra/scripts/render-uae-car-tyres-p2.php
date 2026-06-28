<?php
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\State;

require '/var/www/magento/app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();

/** @var State $state */
$state = $om->get(State::class);
try { $state->setAreaCode('frontend'); } catch (Exception $e) {}

$_SERVER['REQUEST_URI'] = '/en/all-tyres/car-tyres.html?p=2';
$_GET['p'] = 2;

/** @var \Magento\Framework\App\Http $app */
$app = $om->get(\Magento\Framework\App\Http::class);
try {
    ob_start();
    $app->launch();
    $html = ob_get_clean();
    echo 'OK len=' . strlen($html) . "\n";
    echo 'product-item-info=' . substr_count($html, 'product-item-info') . "\n";
    echo 'products-grid=' . substr_count($html, 'products-grid') . "\n";
} catch (Throwable $e) {
    ob_end_clean();
    echo 'EXCEPTION: ' . $e->getMessage() . "\n";
    echo $e->getFile() . ':' . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}
