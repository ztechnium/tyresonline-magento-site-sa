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

// Simulate checkout_index_index via layout handles like the real request
$request = $om->get(Magento\Framework\App\Request\Http::class);
$request->setRouteName('checkout')->setControllerName('index')->setActionName('index');
$request->setModuleName('checkout');

$pageFactory = $om->get(Magento\Framework\View\Result\PageFactory::class);
$page = $pageFactory->create();
$page->addHandle('checkout_index_index');

try {
    $layout = $page->getLayout();
    $layout->generateXml();
    $layout->generateElements();
    $block = $layout->getBlock('checkout.root');
    echo 'checkout_root=' . ($block ? 'found' : 'MISSING') . PHP_EOL;
    if ($block) {
        try {
            $html = $block->toHtml();
            echo 'block_html=' . strlen($html) . PHP_EOL;
            echo 'has_checkout_div=' . (strpos($html, 'id="checkout"') !== false ? 'yes' : 'no') . PHP_EOL;
        } catch (Throwable $e) {
            echo 'block_error=' . $e->getMessage() . PHP_EOL;
        }
    } else {
        foreach ($layout->getAllBlocks() as $name => $b) {
            if (stripos($name, 'checkout') !== false) echo "block:$name\n";
        }
    }
} catch (Throwable $e) {
    echo 'layout_error=' . $e->getMessage() . PHP_EOL;
}
