#!/usr/bin/env php
<?php
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\State;
use Magento\Store\Model\StoreManager;

require '/var/www/magento/app/bootstrap.php';
$params = $_SERVER;
$params[StoreManager::PARAM_RUN_CODE] = 'en';
$params[StoreManager::PARAM_RUN_TYPE] = 'store';
$bootstrap = Bootstrap::create(BP, $params);
$om = $bootstrap->getObjectManager();

/** @var State $state */
$state = $om->get(State::class);
try { $state->setAreaCode('frontend'); } catch (\Exception $e) {}

$productRepository = $om->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
$cart = $om->get(\Magento\Checkout\Model\Cart::class);
$cart->truncate()->save();
$product = $productRepository->getById(4715, false, null, true);
$cart->addProduct($product, ['qty' => 4]);
$cart->save();

/** @var \Magento\Framework\App\Request\Http $request */
$request = $om->get(\Magento\Framework\App\Request\Http::class);
$request->setRouteName('checkout')->setControllerName('cart')->setActionName('index');
$request->setDispatched(false);

/** @var \Magento\Framework\App\View $view */
$view = $om->get(\Magento\Framework\App\View::class);
$view->loadLayout(['checkout_cart_index'], true, true);
$view->renderLayout();

$response = $om->get(\Magento\Framework\App\Response\Http::class);
$html = $response->getBody();
if (!$html) {
    $layout = $view->getLayout();
    $html = $layout->getOutput();
}

echo 'html_len=' . strlen($html) . PHP_EOL;
foreach (['checkoutConfig', 'block-shipping', 'product-item-name', 'cart-totals'] as $pat) {
    echo $pat . '=' . substr_count($html, $pat) . PHP_EOL;
}

$shipping = $view->getLayout()->getBlock('checkout.cart.shipping');
echo 'shipping_in_layout=' . ($shipping ? 'yes' : 'no') . PHP_EOL;
