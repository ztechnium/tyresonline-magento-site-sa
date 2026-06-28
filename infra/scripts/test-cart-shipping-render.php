#!/usr/bin/env php
<?php
require '/var/www/magento/app/bootstrap.php';
$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$state = $om->get(\Magento\Framework\App\State::class);
try { $state->setAreaCode('frontend'); } catch (\Exception $e) {}
$om->get(\Magento\Framework\App\AreaList::class)->getArea('frontend')->load();
$om->get(\Magento\Framework\View\DesignInterface::class)->setDesignTheme('Hditsol/tyresonline', 'frontend');

$storeManager = $om->get(\Magento\Store\Model\StoreManagerInterface::class);
$storeManager->setCurrentStore('en');

$productRepository = $om->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
$cart = $om->get(\Magento\Checkout\Model\Cart::class);
$cart->truncate()->save();
$product = $productRepository->getById(4715, false, null, true);
$cart->addProduct($product, ['qty' => 4]);
$cart->save();

$checkoutSession = $om->get(\Magento\Checkout\Model\Session::class);
echo 'quote_id=' . $checkoutSession->getQuoteId() . PHP_EOL;
echo 'items=' . count($checkoutSession->getQuote()->getAllVisibleItems()) . PHP_EOL;

$layoutFactory = $om->get(\Magento\Framework\View\LayoutFactory::class);
$layout = $layoutFactory->create(['cacheable' => false]);
$layout->getUpdate()->addHandle('default');
$layout->getUpdate()->addHandle('checkout_cart_index');
$layout->getUpdate()->load();
$layout->generateXml();
$layout->generateElements();

$shipping = $layout->getBlock('checkout.cart.shipping');
echo 'shipping_block=' . ($shipping ? 'yes' : 'no') . PHP_EOL;
if (!$shipping) {
    exit(1);
}

try {
    $html = $shipping->toHtml();
    echo 'shipping_html_len=' . strlen($html) . PHP_EOL;
    echo 'has_checkoutConfig=' . (strpos($html, 'checkoutConfig') !== false ? 'yes' : 'no') . PHP_EOL;
    echo 'has_block_shipping=' . (strpos($html, 'block-shipping') !== false ? 'yes' : 'no') . PHP_EOL;
} catch (\Throwable $e) {
    echo 'shipping_render=FAIL: ' . get_class($e) . ': ' . $e->getMessage() . PHP_EOL;
}

$totals = $layout->getBlock('checkout.cart.totals');
if ($totals) {
    try {
        $th = $totals->toHtml();
        echo 'totals_html_len=' . strlen($th) . PHP_EOL;
    } catch (\Throwable $e) {
        echo 'totals_render=FAIL: ' . $e->getMessage() . PHP_EOL;
    }
}
