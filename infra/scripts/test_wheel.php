<?php
use Magento\Framework\App\Bootstrap;

require '/var/www/magento/app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$state = $om->get(\Magento\Framework\App\State::class);
try {
    $state->setAreaCode('frontend');
} catch (\Exception $e) {
}
$om->get(\Magento\Store\Model\StoreManagerInterface::class)->setCurrentStore('en');

echo "Testing wheel-protectors block...\n";
try {
    $layout = $om->get(\Magento\Framework\View\LayoutInterface::class);
    $block = $layout->createBlock(\Magento\Framework\View\Element\Template::class);
    $block->setTemplate('Hdweb_Tyrefinder::wheel-protectors-products-and-after.phtml');
    $html = $block->toHtml();
    echo "OK len=" . strlen($html) . "\n";
    if (stripos($html, 'doctype') !== false) {
        echo "WARNING: contains doctype\n";
    }
} catch (\Throwable $e) {
    echo "FAIL: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
