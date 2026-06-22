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
$layout->generateElements();

echo 'has_content=' . ($layout->hasElement('content') ? 'yes' : 'no') . PHP_EOL;
echo 'has_checkout_root=' . ($layout->hasElement('checkout.root') ? 'yes' : 'no') . PHP_EOL;
echo 'getBlock_content=' . ($layout->getBlock('content') ? 'yes' : 'no') . PHP_EOL;
echo 'getBlock_checkout.root=' . ($layout->getBlock('checkout.root') ? 'yes' : 'no') . PHP_EOL;

$childNames = $layout->getChildNames('content');
echo 'content_children=' . implode(',', $childNames) . PHP_EOL;

// Try rendering content container
$contentBlock = $layout->getBlock('content');
if ($contentBlock) {
    echo 'content_class=' . get_class($contentBlock) . PHP_EOL;
}

// Render checkout.root element directly
try {
    $html = $layout->renderElement('checkout.root');
    echo 'render_checkout_root_len=' . strlen($html) . PHP_EOL;
    echo 'render_has_config=' . (strpos($html, 'checkoutConfig') !== false ? 'yes' : 'no') . PHP_EOL;
} catch (Throwable $e) {
    echo 'render_ERR: ' . get_class($e) . ': ' . $e->getMessage() . PHP_EOL;
}

try {
    $html = $layout->renderElement('content');
    echo 'render_content_len=' . strlen($html) . PHP_EOL;
    echo 'render_content_has_config=' . (strpos($html, 'checkoutConfig') !== false ? 'yes' : 'no') . PHP_EOL;
    if (preg_match('/checkout-messages|checkoutConfig|id=\"checkout\"/', $html, $m)) {
        echo 'content_match=' . $m[0] . PHP_EOL;
    }
} catch (Throwable $e) {
    echo 'render_content_ERR: ' . get_class($e) . ': ' . $e->getMessage() . PHP_EOL;
}
