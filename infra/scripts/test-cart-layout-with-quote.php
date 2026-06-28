<?php
require '/var/www/magento/app/bootstrap.php';
$params = $_SERVER;
$params[\Magento\Store\Model\StoreManager::PARAM_RUN_CODE] = 'en';
$params[\Magento\Store\Model\StoreManager::PARAM_RUN_TYPE] = 'store';
$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $params);
/** @var \Magento\Framework\App\Http $app */
$app = $bootstrap->createApplication(\Magento\Framework\App\Http::class);
$om = $bootstrap->getObjectManager();
$state = $om->get(\Magento\Framework\App\State::class);
try { $state->setAreaCode('frontend'); } catch (\Exception $e) {}

$storeManager = $om->get(\Magento\Store\Model\StoreManagerInterface::class);
$storeManager->setCurrentStore('en');

$productRepository = $om->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
$cart = $om->get(\Magento\Checkout\Model\Cart::class);
$cart->truncate()->save();
$product = $productRepository->getById(4715);
$cart->addProduct($product, 4);
$cart->save();

$checkoutSession = $om->get(\Magento\Checkout\Model\Session::class);
echo 'quote_id=' . $checkoutSession->getQuoteId() . PHP_EOL;
echo 'items=' . $checkoutSession->getQuote()->getItemsCount() . PHP_EOL;

$layoutFactory = $om->get(\Magento\Framework\View\LayoutFactory::class);
$layout = $layoutFactory->create(['cacheable' => false]);
$layout->getUpdate()->addHandle('checkout_cart_index');
$layout->getUpdate()->load();
$layout->generateXml();
$layout->generateElements();

$names = ['checkout.cart.shipping', 'checkout.cart.totals', 'checkout.cart', 'cart.summary'];
foreach ($names as $name) {
    $block = $layout->getBlock($name);
    echo $name . '=' . ($block ? get_class($block) : 'NULL') . PHP_EOL;
}

$shipping = $layout->getBlock('checkout.cart.shipping');
if ($shipping) {
    try {
        $html = $shipping->toHtml();
        echo 'shipping_html_len=' . strlen($html) . PHP_EOL;
        echo 'has_checkoutConfig=' . (strpos($html, 'checkoutConfig') !== false ? 'yes' : 'no') . PHP_EOL;
    } catch (\Throwable $e) {
        echo 'shipping_render=FAIL: ' . $e->getMessage() . PHP_EOL;
    }
}
