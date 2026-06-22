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

$checkoutSession = $session;
$quote2 = $checkoutSession->getQuote();
echo 'quote_items=' . $quote2->getItemsCount() . PHP_EOL;
echo 'pickup_store=' . $quote2->getPickupStore() . PHP_EOL;

$helper = $om->get(Hdweb\Core\Helper\Data::class);
try {
    $vehicles = $helper->getOnestepCheckoutVehcilelist();
    echo 'vehicles=' . count($vehicles) . PHP_EOL;
} catch (Throwable $e) {
    echo 'vehicle_error=' . $e->getMessage() . PHP_EOL;
}

// Build checkout page via front controller simulation
$request = $om->create(Magento\Framework\App\Request\Http::class);
$response = $om->create(Magento\Framework\App\Response\Http::class);
$request->setModuleName('checkout')->setControllerName('index')->setActionName('index');
$request->setRouteName('checkout')->setDispatched(false);

try {
    $frontController = $om->get(Magento\Framework\App\FrontController::class);
    $result = $frontController->dispatch($request);
    $body = $response->getBody();
    if ($result instanceof Magento\Framework\Controller\ResultInterface) {
        $om->get(Magento\Framework\App\Response\HttpInterface::class);
        ob_start();
        $result->renderResult($om->get(Magento\Framework\App\Response\HttpInterface::class));
        $body = ob_get_clean();
    }
    echo 'render_size=' . strlen((string)$body) . PHP_EOL;
    echo 'has_checkout_config=' . (strpos((string)$body, 'checkoutConfig') !== false ? 'yes' : 'no') . PHP_EOL;
    echo 'has_checkout_div=' . (strpos((string)$body, 'id="checkout"') !== false ? 'yes' : 'no') . PHP_EOL;
    echo 'has_fatal=' . (preg_match('/Fatal error|CredisException/i', (string)$body) ? 'yes' : 'no') . PHP_EOL;
} catch (Throwable $e) {
    echo 'render_error=' . get_class($e) . ': ' . $e->getMessage() . PHP_EOL;
}
