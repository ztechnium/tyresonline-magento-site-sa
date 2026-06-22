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

$pageFactory = $om->get(Magento\Framework\View\Result\PageFactory::class);
$page = $pageFactory->create(false, ['cacheable' => false]);
$page->addHandle('checkout_index_index');
$page->addHandle('onestepcheckout');
$layout = $page->getLayout();
$layout->generateXml();

try {
    $layout->generateElements();
    echo 'generateElements=ok' . PHP_EOL;
} catch (Throwable $e) {
    echo 'generateElements_ERR: ' . $e->getMessage() . PHP_EOL;
}

try {
    $block = $layout->createBlock(Magento\Checkout\Block\Onepage::class, 'manual.checkout.root');
    echo 'createBlock=ok class=' . get_class($block) . PHP_EOL;
    $js = $block->getJsLayout();
    echo 'jslayout_len=' . strlen($js) . PHP_EOL;
    $html = $block->toHtml();
    echo 'toHtml_len=' . strlen($html) . PHP_EOL;
    echo 'has_config=' . (strpos($html, 'checkoutConfig') !== false ? 'yes' : 'no') . PHP_EOL;
} catch (Throwable $e) {
    echo 'createBlock_ERR: ' . get_class($e) . ': ' . $e->getMessage() . PHP_EOL;
    echo $e->getFile() . ':' . $e->getLine() . PHP_EOL;
}

echo 'scheduled_checkout_root=' . ($layout->hasElement('checkout.root') ? 'yes' : 'no') . PHP_EOL;
echo 'getBlock_checkout_root=' . ($layout->getBlock('checkout.root') ? 'yes' : 'no') . PHP_EOL;
