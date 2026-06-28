<?php
require '/var/www/magento/app/bootstrap.php';
$bootstrap = Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$state = $om->get(Magento\Framework\App\State::class);
try {
    $state->setAreaCode('frontend');
} catch (Exception $e) {
}
$om->get(Magento\Store\Model\StoreManagerInterface::class)->setCurrentStore('en');
$layout = $om->get(Magento\Framework\View\LayoutInterface::class);
$layout->getUpdate()->addHandle('checkout_index_index')->addHandle('onestepcheckout');
$layout->generateXml();
$block = $layout->createBlock(Magento\Checkout\Block\Onepage::class);
$jl = json_decode($block->getJsLayout(), true);
$renders = $jl['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['renders']['children'] ?? null;
echo 'renders=' . (is_array($renders) ? implode(',', array_keys($renders)) : 'null') . PHP_EOL;
$config = $block->getCheckoutConfig();
echo 'paymentMethods=' . count($config['paymentMethods'] ?? []) . PHP_EOL;
if (!empty($config['paymentMethods'])) {
    echo implode(',', array_keys($config['paymentMethods'])) . PHP_EOL;
}
