<?php
require '/var/www/magento/app/bootstrap.php';
$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$state = $om->get(\Magento\Framework\App\State::class);
try { $state->setAreaCode('frontend'); } catch (\Exception $e) {}

echo 'class_exists=' . (class_exists(\Magento\Checkout\Block\Cart\Shipping::class) ? 'yes' : 'no') . PHP_EOL;

try {
    $layout = $om->create(\Magento\Framework\View\Layout::class);
    $block = $layout->createBlock(\Magento\Checkout\Block\Cart\Shipping::class, 'test.shipping');
    echo 'createBlock=OK' . PHP_EOL;
    $cfg = $block->getCheckoutConfig();
    echo 'config_keys=' . count($cfg) . PHP_EOL;
} catch (\Throwable $e) {
    echo 'createBlock=FAIL: ' . get_class($e) . ': ' . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}

try {
    $pageLayout = $om->create(\Magento\Framework\View\LayoutInterface::class);
    $layoutFactory = $om->get(\Magento\Framework\View\LayoutFactory::class);
    $layout = $layoutFactory->create(['cacheable' => false]);
    $layout->getUpdate()->addHandle('checkout_cart_index');
    $layout->getUpdate()->load();
    $layout->generateXml();
    $layout->generateElements();
    $shipping = $layout->getBlock('checkout.cart.shipping');
    echo 'layout_block=' . ($shipping ? get_class($shipping) : 'NULL') . PHP_EOL;
    if ($shipping) {
        echo 'serialized_len=' . strlen($shipping->getSerializedCheckoutConfig()) . PHP_EOL;
    }
} catch (\Throwable $e) {
    echo 'layout=FAIL: ' . $e->getMessage() . PHP_EOL;
}
