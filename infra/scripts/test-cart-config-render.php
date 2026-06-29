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

$layout = $om->create(\Magento\Framework\View\Layout::class);
$configBlock = $layout->createBlock(
    \Magento\Checkout\Block\Cart\Shipping::class,
    'checkout.cart.inline_config',
    ['template' => 'Magento_Checkout::cart/checkout-config.phtml']
);

try {
    $serialized = $configBlock->getSerializedCheckoutConfig();
    echo 'serialized_len=' . strlen($serialized) . PHP_EOL;
    $html = $configBlock->toHtml();
    echo 'config_html_len=' . strlen($html) . PHP_EOL;
    echo 'config_has_checkoutConfig=' . (strpos($html, 'checkoutConfig') !== false ? 'yes' : 'no') . PHP_EOL;
    if (strlen($html) < 500) {
        echo 'config_html=' . $html . PHP_EOL;
    }
} catch (\Throwable $e) {
    echo 'config_FAIL: ' . $e->getMessage() . PHP_EOL;
}

$request = $om->get(\Magento\Framework\App\Request\Http::class);
$request->setRouteName('checkout')->setControllerName('cart')->setActionName('index');
$view = $om->get(\Magento\Framework\App\View::class);
$view->loadLayout(['checkout_cart_index'], true, true);
$layout = $view->getLayout();

$summary = $layout->getBlock('cart.summary');
echo 'cart.summary=' . ($summary ? 'yes' : 'no') . PHP_EOL;
if ($summary) {
    echo 'summary_children=' . implode(',', $summary->getChildNames()) . PHP_EOL;
    echo 'summary_html_len=' . strlen($summary->toHtml()) . PHP_EOL;
}

$totals = $layout->getBlock('checkout.cart.totals');
if ($totals) {
    echo 'totals_template=' . $totals->getTemplate() . PHP_EOL;
    echo 'totals_parent=' . ($totals->getParentBlock() ? $totals->getParentBlock()->getNameInLayout() : 'none') . PHP_EOL;
}
