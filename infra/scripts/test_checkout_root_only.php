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

try {
    $html = $layout->renderNonCachedElement('checkout.root');
    echo 'render_len=' . strlen($html) . PHP_EOL;
    echo 'has_config=' . (strpos($html, 'checkoutConfig') !== false ? 'yes' : 'no') . PHP_EOL;
    echo 'has_checkout_div=' . (strpos($html, 'id="checkout"') !== false ? 'yes' : 'no') . PHP_EOL;
    if ($html) {
        echo substr($html, 0, 500) . PHP_EOL;
    }
} catch (Throwable $e) {
    echo 'ERR: ' . get_class($e) . ': ' . $e->getMessage() . PHP_EOL;
    echo $e->getFile() . ':' . $e->getLine() . PHP_EOL;
}
