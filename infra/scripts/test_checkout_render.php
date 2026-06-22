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
$searchCriteria = $om->create(Magento\Framework\Api\SearchCriteriaBuilder::class)
    ->setPageSize(1)
    ->create();
$items = $om->get(Magento\Catalog\Api\ProductRepositoryInterface::class)
    ->getList($searchCriteria)
    ->getItems();
$product = reset($items);
if (!$product) {
    echo "no_product\n";
    exit(1);
}

$cartManagement = $om->get(Magento\Quote\Api\CartManagementInterface::class);
$cartRepository = $om->get(Magento\Quote\Api\CartRepositoryInterface::class);
$cartId = $cartManagement->createEmptyCart();
$quote = $cartRepository->get($cartId);
$quote->setStoreId($storeManager->getStore()->getId());

$quoteItem = $om->create(Magento\Quote\Model\Quote\Item::class);
$quoteItem->setProduct($product);
$quoteItem->setQty(4);
$quote->addItem($quoteItem);
$quote->collectTotals();
$cartRepository->save($quote);

$session = $om->get(Magento\Checkout\Model\Session::class);
$session->setQuoteId($cartId);

$request = $om->get(Magento\Framework\App\Request\Http::class);
$request->setRouteName('checkout');
$request->setControllerName('index');
$request->setActionName('index');

$pageFactory = $om->get(Magento\Framework\View\Result\PageFactory::class);
$page = $pageFactory->create();
$html = $page->getLayout()->getOutput();
echo 'html_size=' . strlen($html) . PHP_EOL;
echo (strpos($html, 'checkout-container') !== false || strpos($html, 'opc-wrapper') !== false ? 'checkout_markup=yes' : 'checkout_markup=no') . PHP_EOL;
echo (strpos($html, 'getOnestepCheckoutVehcilelist') !== false ? 'error_leak=yes' : 'error_leak=no') . PHP_EOL;
