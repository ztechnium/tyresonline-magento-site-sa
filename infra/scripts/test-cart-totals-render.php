#!/usr/bin/env php
<?php
declare(strict_types=1);
require '/var/www/magento/app/bootstrap.php';
$params = $_SERVER;
$params[\Magento\Store\Model\StoreManager::PARAM_RUN_CODE] = 'en';
$params[\Magento\Store\Model\StoreManager::PARAM_RUN_TYPE] = 'store';
$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $params);
$om = $bootstrap->getObjectManager();
$state = $om->get(\Magento\Framework\App\State::class);
try {
    $state->setAreaCode('frontend');
} catch (\Exception $e) {
}

$om->get(\Magento\Framework\App\AreaList::class)->getArea('frontend')->load();
$om->get(\Magento\Framework\View\DesignInterface::class)->setDesignTheme('Hditsol/tyresonline', 'frontend');

$cart = $om->get(\Magento\Checkout\Model\Cart::class);
$productRepository = $om->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
$cart->truncate()->save();
$product = $productRepository->getById(4715, false, null, true);
$cart->addProduct($product, ['qty' => 4]);
$cart->save();

$request = $om->get(\Magento\Framework\App\Request\Http::class);
$request->setRouteName('checkout')->setControllerName('cart')->setActionName('index');
$view = $om->get(\Magento\Framework\App\View::class);
$view->loadLayout(['checkout_cart_index'], true, true);
$layout = $view->getLayout();

$totals = $layout->getBlock('checkout.cart.totals');
echo 'totals_block=' . ($totals ? get_class($totals) : 'NULL') . PHP_EOL;
if ($totals) {
    try {
        $html = $totals->toHtml();
        echo 'totals_html_len=' . strlen($html) . PHP_EOL;
        echo 'has_checkoutConfig=' . (strpos($html, 'checkoutConfig') !== false ? 'yes' : 'no') . PHP_EOL;
        echo 'has_cart-totals=' . (strpos($html, 'cart-totals') !== false ? 'yes' : 'no') . PHP_EOL;
    } catch (\Throwable $e) {
        echo 'totals_render_FAIL: ' . $e->getMessage() . PHP_EOL;
    }
}

$view->renderLayout();
$html = $layout->getOutput();
echo 'page_checkoutConfig=' . substr_count($html, 'checkoutConfig') . PHP_EOL;
echo 'page_cart-totals=' . substr_count($html, 'cart-totals') . PHP_EOL;
