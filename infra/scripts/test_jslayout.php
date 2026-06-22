<?php
require '/var/www/magento/app/bootstrap.php';
$bootstrap = Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$state = $om->get(Magento\Framework\App\State::class);
try { $state->setAreaCode('frontend'); } catch (Exception $e) {}
$storeManager = $om->get(Magento\Store\Model\StoreManagerInterface::class);
$storeManager->setCurrentStore('en');

$product = $om->get(Magento\Catalog\Api\ProductRepositoryInterface::class)->getById(4898);
$cartManagement = $om->get(Magento\Quote\Api\CartManagementInterface::class);
$cartRepository = $om->get(Magento\Quote\Api\CartRepositoryInterface::class);
$cartId = $cartManagement->createEmptyCart();
$quote = $cartRepository->get($cartId);
$quote->setStoreId($storeManager->getStore()->getId());
$quote->addProduct($product, 4);
$quote->setPickupDate('2026-06-25');
$quote->setPickupTime('09:00 - 11:00');
$quote->setPickupStore('1');
$quote->collectTotals();
$cartRepository->save($quote);
$om->get(Magento\Checkout\Model\Session::class)->setQuoteId($cartId);

$config = $om->get(Ecomteck\OneStepCheckout\Helper\Config::class);
echo 'onestep=' . ($config->isEnabled() ? 'yes' : 'no') . PHP_EOL;

$layoutFactory = $om->get(Magento\Framework\View\LayoutFactory::class);
$layout = $layoutFactory->create(['cacheable' => false]);
$update = $layout->getUpdate();
$update->addHandle('default');
$update->addHandle('checkout_index_index');
if ($config->isEnabled()) {
    $update->addHandle('onestepcheckout');
}
$layout->generateXml();
$layout->generateElements();

echo 'has_content=' . ($layout->hasElement('content') ? 'yes' : 'no') . PHP_EOL;
echo 'has_checkout_root=' . ($layout->hasElement('checkout.root') ? 'yes' : 'no') . PHP_EOL;

$block = $layout->getBlock('checkout.root');
if ($block) {
    try {
        $js = $block->getJsLayout();
        echo 'jslayout_len=' . strlen($js) . PHP_EOL;
        $html = $block->toHtml();
        echo 'html_len=' . strlen($html) . PHP_EOL;
    } catch (Throwable $e) {
        echo 'ERROR: ' . get_class($e) . ': ' . $e->getMessage() . PHP_EOL;
        echo $e->getFile() . ':' . $e->getLine() . PHP_EOL;
    }
} else {
    echo "checkout.root not in layout\n";
    echo "blocks in content: ";
    // list child blocks
}
