<?php
require '/var/www/magento/app/bootstrap.php';
$bootstrap = Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$state = $om->get(Magento\Framework\App\State::class);
try { $state->setAreaCode('frontend'); } catch (Exception $e) {}
$env = include BP . '/app/etc/env.php';
echo 'redis_host=' . ($env['cache']['frontend']['default']['backend_options']['server'] ?? 'MISSING') . PHP_EOL;
$helper = $om->get(Hdweb\Core\Helper\Data::class);
try {
    $list = $helper->getOnestepCheckoutVehcilelist();
    echo 'vehicle_makes=' . count($list) . PHP_EOL;
} catch (Throwable $e) {
    echo 'HELPER_ERR: ' . $e->getMessage() . PHP_EOL;
}
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

$layoutFactory = $om->get(Magento\Framework\View\LayoutFactory::class);
$layout = $layoutFactory->create(['cacheable' => false]);
$update = $layout->getUpdate();
$update->addHandle('default');
$update->addHandle('checkout_index_index');
$update->addHandle('onestepcheckout');
$layout->generateXml();
$layout->generateElements();
$block = $layout->getBlock('checkout.root');
if (!$block) { echo 'no checkout.root block' . PHP_EOL; exit; }
try {
    $js = $block->getJsLayout();
    echo 'jslayout_len=' . strlen($js) . PHP_EOL;
    $html = $block->toHtml();
    echo 'html_len=' . strlen($html) . PHP_EOL;
    echo (strpos($html, 'checkoutConfig') !== false ? 'has_checkoutConfig=yes' : 'has_checkoutConfig=no') . PHP_EOL;
} catch (Throwable $e) {
    echo 'BLOCK_ERR: ' . get_class($e) . ': ' . $e->getMessage() . PHP_EOL;
}
