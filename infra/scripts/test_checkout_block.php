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

$helper = $om->get(Hdweb\Core\Helper\Data::class);
try {
    $list = $helper->getOnestepCheckoutVehcilelist();
    echo 'vehicle_makes=' . count($list) . PHP_EOL;
} catch (Throwable $e) {
    echo 'helper_ERR: ' . $e->getMessage() . PHP_EOL;
}

$pageFactory = $om->get(Magento\Framework\View\Result\PageFactory::class);
$page = $pageFactory->create(false, ['cacheable' => false]);
$page->addHandle('checkout_index_index');
$page->addHandle('onestepcheckout');
$layout = $page->getLayout();
$layout->generateXml();
$layout->generateElements();

// Force block creation via layout render callback
$layout->getOutput(); // may fail partially

// Use layout's internal block collection
$all = $layout->getAllBlocks();
foreach ($all as $name => $block) {
    if ($block->getNameInLayout() === 'checkout.root') {
        echo 'found_checkout_root_block=yes' . PHP_EOL;
        try {
            $js = $block->getJsLayout();
            echo 'jslayout_len=' . strlen($js) . PHP_EOL;
            $html = $block->toHtml();
            echo 'toHtml_len=' . strlen($html) . PHP_EOL;
        } catch (Throwable $e) {
            echo 'block_ERR: ' . get_class($e) . ': ' . $e->getMessage() . PHP_EOL;
            echo $e->getFile() . ':' . $e->getLine() . PHP_EOL;
        }
        exit;
    }
}

echo 'found_checkout_root_block=no total_blocks=' . count($all) . PHP_EOL;

// Try rendering element with output buffering and error handler
set_error_handler(function ($errno, $errstr, $file, $line) {
    echo "PHP_ERR[$errno]: $errstr in $file:$line" . PHP_EOL;
    return false;
});
try {
    ob_start();
    $html = $layout->renderNonCachedElement('checkout.root');
    $out = ob_get_clean();
    echo 'render_len=' . strlen($html) . PHP_EOL;
    echo 'ob_len=' . strlen($out) . PHP_EOL;
    if ($html) {
        echo 'has_config=' . (strpos($html, 'checkoutConfig') !== false ? 'yes' : 'no') . PHP_EOL;
        echo substr($html, 0, 300) . PHP_EOL;
    }
} catch (Throwable $e) {
    ob_end_clean();
    echo 'render_ERR: ' . get_class($e) . ': ' . $e->getMessage() . PHP_EOL;
    echo $e->getFile() . ':' . $e->getLine() . PHP_EOL;
}
