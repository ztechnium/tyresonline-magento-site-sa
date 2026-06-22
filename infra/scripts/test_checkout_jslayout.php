<?php
require '/var/www/magento/app/bootstrap.php';
$bootstrap = Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$state = $om->get(Magento\Framework\App\State::class);
try {
    $state->setAreaCode('frontend');
} catch (Exception $e) {
}

$storeManager = $om->get(Magento\Store\Model\StoreManagerInterface::class);
$storeManager->setCurrentStore('en');

$layout = $om->create(Magento\Framework\View\Layout::class);
$block = $layout->createBlock(Magento\Checkout\Block\Onepage::class);
$jsLayout = $block->getJsLayout();
$data = json_decode($jsLayout, true);
echo 'jslayout_ok=yes' . PHP_EOL;
echo 'components=' . count($data['components'] ?? []) . PHP_EOL;
