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
$pageFactory = $om->get(Magento\Framework\View\Result\PageFactory::class);
$page = $pageFactory->create(false, ['cacheable' => false]);
$page->addHandle('checkout_index_index');
if ($config->isEnabled()) {
    $page->addHandle('onestepcheckout');
}
$pageLayout = $page->getLayout();
$pageLayout->generateXml();
$pageLayout->generateElements();

$names = [];
foreach ($pageLayout->getAllBlocks() as $block) {
    $names[] = $block->getNameInLayout();
}
sort($names);
echo 'blocks_with_checkout=' . implode(',', array_filter($names, fn($n) => stripos($n, 'checkout') !== false)) . PHP_EOL;
echo 'has_checkout_root=' . ($pageLayout->hasElement('checkout.root') ? 'yes' : 'no') . PHP_EOL;
echo 'getBlock_checkout.root=' . ($pageLayout->getBlock('checkout.root') ? 'yes' : 'no') . PHP_EOL;

// Try createBlock manually
try {
    $block = $pageLayout->createBlock(Magento\Checkout\Block\Onepage::class, 'checkout.root.test');
    echo 'manual_create=' . ($block ? 'ok' : 'fail') . PHP_EOL;
} catch (Throwable $e) {
    echo 'manual_create_ERR: ' . $e->getMessage() . PHP_EOL;
}

// Render full page output snippet
try {
    $html = $pageLayout->getOutput();
    echo 'full_output_len=' . strlen($html) . PHP_EOL;
    echo 'full_has_checkoutConfig=' . (strpos($html, 'checkoutConfig') !== false ? 'yes' : 'no') . PHP_EOL;
    echo 'full_has_checkout_div=' . (strpos($html, 'id="checkout"') !== false ? 'yes' : 'no') . PHP_EOL;
    if (preg_match('/<div class=\"column main\">(.*?)<\/div>\s*<\/div>\s*<\/main>/s', $html, $m)) {
        echo 'main_snippet=' . substr(strip_tags($m[1]), 0, 200) . PHP_EOL;
    }
} catch (Throwable $e) {
    echo 'OUTPUT_ERR: ' . get_class($e) . ': ' . $e->getMessage() . PHP_EOL;
    echo $e->getFile() . ':' . $e->getLine() . PHP_EOL;
}
