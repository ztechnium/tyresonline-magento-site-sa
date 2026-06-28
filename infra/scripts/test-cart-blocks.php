#!/usr/bin/env php
<?php
require '/var/www/magento/app/bootstrap.php';
$params = $_SERVER;
$params[\Magento\Store\Model\StoreManager::PARAM_RUN_CODE] = 'en';
$params[\Magento\Store\Model\StoreManager::PARAM_RUN_TYPE] = 'store';
$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $params);
$om = $bootstrap->getObjectManager();
$state = $om->get(\Magento\Framework\App\State::class);
try { $state->setAreaCode('frontend'); } catch (\Exception $e) {}

$productRepository = $om->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
$cart = $om->get(\Magento\Checkout\Model\Cart::class);
$cart->truncate()->save();
$product = $productRepository->getById(4715, false, null, true);
$cart->addProduct($product, ['qty' => 4]);
$cart->save();

$request = $om->get(\Magento\Framework\App\Request\Http::class);
$request->setRouteName('checkout')->setControllerName('cart')->setActionName('index');
$view = $om->get(\Magento\Framework\App\View::class);
$view->loadLayout(['checkout_cart_index'], true, true);
$layout = $view->getLayout();

$needles = ['cart.summary', 'checkout.cart.shipping', 'checkout.cart.totals', 'cart.bottom.right', 'checkout.cart.form'];
foreach ($needles as $name) {
    $b = $layout->getBlock($name);
    echo "$name=" . ($b ? get_class($b) : 'NULL');
    if ($b && method_exists($b, 'getParentBlock')) {
        $p = $b->getParentBlock();
        echo ' parent=' . ($p ? $p->getNameInLayout() : 'none');
    }
    echo PHP_EOL;
}

$shipping = $layout->getBlock('checkout.cart.shipping');
if ($shipping) {
    $parent = $shipping->getParentBlock();
    echo 'shipping parent=' . ($parent ? $parent->getNameInLayout() : 'NONE') . PHP_EOL;
    if ($parent) {
        echo 'parent child names: ' . implode(', ', $parent->getChildNames()) . PHP_EOL;
    }
    try {
        echo 'shipping toHtml len=' . strlen($shipping->toHtml()) . PHP_EOL;
    } catch (\Throwable $e) {
        echo 'shipping toHtml FAIL: ' . $e->getMessage() . PHP_EOL;
    }
}

$summary = $layout->getBlock('cart.summary');
if ($summary) {
    echo 'summary child names: ' . implode(', ', $summary->getChildNames()) . PHP_EOL;
    try {
        echo 'summary toHtml len=' . strlen($summary->toHtml()) . PHP_EOL;
    } catch (\Throwable $e) {
        echo 'summary toHtml FAIL: ' . $e->getMessage() . PHP_EOL;
    }
}

$all = $layout->getAllBlocks();
echo 'total_blocks=' . count($all) . PHP_EOL;
foreach ($all as $block) {
    if (strpos($block->getNameInLayout(), 'cart') !== false || strpos($block->getNameInLayout(), 'summary') !== false) {
        echo '  ' . $block->getNameInLayout() . PHP_EOL;
    }
}
