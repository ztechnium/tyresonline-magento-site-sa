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

function dumpTree($block, $depth = 0) {
    if (!$block) return;
    $name = $block->getNameInLayout();
    $class = get_class($block);
    echo str_repeat('  ', $depth) . "$name ($class)" . PHP_EOL;
    foreach ($block->getChildNames() as $childName) {
        dumpTree($block->getChildBlock($childName), $depth + 1);
    }
}

$cartBlock = $layout->getBlock('checkout.cart');
echo "=== checkout.cart tree ===" . PHP_EOL;
dumpTree($cartBlock, 0);

$summary = $layout->getBlock('cart.summary');
echo PHP_EOL . 'cart.summary=' . ($summary ? get_class($summary) : 'NULL') . PHP_EOL;
if ($summary) {
    echo 'summary children: ' . implode(', ', $summary->getChildNames()) . PHP_EOL;
    $shipping = $summary->getChildBlock('shipping');
    echo 'summary->shipping=' . ($shipping ? get_class($shipping) : 'NULL') . PHP_EOL;
}

$shipping = $layout->getBlock('checkout.cart.shipping');
echo PHP_EOL . 'checkout.cart.shipping parent=' . ($shipping ? $shipping->getParentBlock()->getNameInLayout() : 'NULL') . PHP_EOL;

if ($summary) {
    try {
        $summaryHtml = $summary->toHtml();
        echo 'summary_html_len=' . strlen($summaryHtml) . PHP_EOL;
        echo 'summary_has_checkoutConfig=' . (strpos($summaryHtml, 'checkoutConfig') !== false ? 'yes' : 'no') . PHP_EOL;
        echo 'summary_has_block_shipping=' . (strpos($summaryHtml, 'block-shipping') !== false ? 'yes' : 'no') . PHP_EOL;
    } catch (\Throwable $e) {
        echo 'summary_render=FAIL: ' . $e->getMessage() . PHP_EOL;
    }
}
