<?php
require '/var/www/magento/app/bootstrap.php';
$bootstrap = Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$state = $om->get(Magento\Framework\App\State::class);
try {
    $state->setAreaCode('frontend');
} catch (Exception $e) {
}

$storeManager = $om->get(Magento\Store\Model\StoreManagerInterface::class);
$storeManager->setCurrentStore('en');

$productRepository = $om->get(Magento\Catalog\Api\ProductRepositoryInterface::class);
$product = $productRepository->getById(4898);

$cartManagement = $om->get(Magento\Quote\Api\CartManagementInterface::class);
$cartRepository = $om->get(Magento\Quote\Api\CartRepositoryInterface::class);
$cartId = $cartManagement->createEmptyCart();
$quote = $cartRepository->get($cartId);
$quote->setStoreId($storeManager->getStore()->getId());
$quote->addProduct($product, 4);
$quote->setPickupDate('2026-06-20');
$quote->setPickupTime('09:00 - 11:00');
$quote->setPickupStore('1');
$quote->collectTotals();
$cartRepository->save($quote);

$session = $om->get(Magento\Checkout\Model\Session::class);
$session->setQuoteId($cartId);

$config = $om->get(Ecomteck\OneStepCheckout\Helper\Config::class);
echo 'onestep_enabled=' . ($config->isEnabled() ? 'yes' : 'no') . PHP_EOL;
echo 'show_vehicle=' . ($quote->getShowVehicleInfo() ? 'yes' : 'no') . PHP_EOL;

$layoutFactory = $om->get(Magento\Framework\View\LayoutFactory::class);
$layout = $layoutFactory->create(['cacheable' => false]);
$layout->getUpdate()->load(['default', 'checkout_index_index', 'onestepcheckout']);
$layout->generateXml();
$layout->generateElements();

$block = $layout->getBlock('checkout.root');
echo 'checkout_root=' . ($block ? 'found' : 'missing') . PHP_EOL;
if (!$block) {
    foreach ($layout->getAllBlocks() as $name => $b) {
        if (stripos($name, 'checkout') !== false) {
            echo "block:$name class=" . get_class($b) . PHP_EOL;
        }
    }
    exit(1);
}

try {
    $html = $block->toHtml();
    echo 'html_size=' . strlen($html) . PHP_EOL;
    echo 'has_checkout_div=' . (strpos($html, 'id="checkout"') !== false ? 'yes' : 'no') . PHP_EOL;
    echo 'has_checkoutConfig=' . (strpos($html, 'checkoutConfig') !== false ? 'yes' : 'no') . PHP_EOL;
} catch (Throwable $e) {
    echo 'html_error=' . get_class($e) . ': ' . $e->getMessage() . PHP_EOL;
    echo $e->getFile() . ':' . $e->getLine() . PHP_EOL;
}

try {
    $jsLayout = $block->getJsLayout();
    echo 'jslayout_size=' . strlen($jsLayout) . PHP_EOL;
} catch (Throwable $e) {
    echo 'jslayout_error=' . get_class($e) . ': ' . $e->getMessage() . PHP_EOL;
    echo $e->getFile() . ':' . $e->getLine() . PHP_EOL;
}
