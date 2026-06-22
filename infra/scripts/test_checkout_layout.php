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

$pageFactory = $om->get(Magento\Framework\View\Result\PageFactory::class);
$page = $pageFactory->create();
$page->addHandle('checkout_index_index');
$page->addHandle('onestepcheckout_index_index');

try {
    $layout = $page->getLayout();
    $layout->generateXml();
    $layout->generateElements();
    $block = $layout->getBlock('checkout.root');
    if (!$block) {
        echo "checkout.root=missing\n";
        foreach ($layout->getAllBlocks() as $name => $b) {
            if (stripos($name, 'checkout') !== false) {
                echo "block:$name\n";
            }
        }
        exit(1);
    }
    $html = $block->toHtml();
    echo 'block_html_size=' . strlen($html) . PHP_EOL;
    echo 'has_checkout_div=' . (strpos($html, 'id="checkout"') !== false ? 'yes' : 'no') . PHP_EOL;
    try {
        $jsLayout = $block->getJsLayout();
        echo 'jslayout_size=' . strlen($jsLayout) . PHP_EOL;
    } catch (Throwable $e) {
        echo 'jslayout_error=' . get_class($e) . ': ' . $e->getMessage() . PHP_EOL;
    }
} catch (Throwable $e) {
    echo 'layout_error=' . get_class($e) . ': ' . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}
