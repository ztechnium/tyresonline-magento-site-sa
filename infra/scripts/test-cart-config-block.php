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

$configBlock = $layout->getBlock('checkout.cart.checkout_config');
echo 'checkout_config_block=' . ($configBlock ? get_class($configBlock) : 'NULL') . PHP_EOL;

$totalsContainer = $layout->getBlock('checkout.cart.totals.container');
echo 'totals_container=' . ($totalsContainer ? get_class($totalsContainer) : 'NULL') . PHP_EOL;

if ($configBlock) {
    try {
        $html = $configBlock->toHtml();
        echo 'config_html_len=' . strlen($html) . PHP_EOL;
        echo 'has_checkoutConfig=' . (strpos($html, 'checkoutConfig') !== false ? 'yes' : 'no') . PHP_EOL;
    } catch (\Throwable $e) {
        echo 'config_render=FAIL: ' . $e->getMessage() . PHP_EOL;
    }
}

$view->renderLayout();
$html = $view->getLayout()->getOutput();
echo 'page_checkoutConfig=' . substr_count($html, 'checkoutConfig') . PHP_EOL;
