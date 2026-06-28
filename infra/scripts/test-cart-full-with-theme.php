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
$om->get(\Magento\Framework\App\AreaList::class)->getArea('frontend')->load();
$om->get(\Magento\Framework\View\DesignInterface::class)->setDesignTheme('Hditsol/tyresonline', 'frontend');

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
echo 'config_block=' . ($configBlock ? 'yes' : 'no') . PHP_EOL;
if ($configBlock) {
    echo 'config_html_len=' . strlen($configBlock->toHtml()) . PHP_EOL;
}

$view->renderLayout();
$html = $layout->getOutput();
echo 'page_len=' . strlen($html) . PHP_EOL;
echo 'checkoutConfig=' . substr_count($html, 'checkoutConfig') . PHP_EOL;

$totalsBlock = $layout->getBlock('checkout.cart.totals');
if ($totalsBlock) {
    echo 'totals_in_output=' . (strpos($html, 'cart-totals') !== false ? 'yes' : 'no') . PHP_EOL;
}
