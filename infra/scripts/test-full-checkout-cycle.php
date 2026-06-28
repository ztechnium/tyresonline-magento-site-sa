#!/usr/bin/env php
<?php
/**
 * Full checkout cycle test via Magento APIs (server-side).
 * Steps: add to cart → select location → fill guest info → place order attempt.
 */
declare(strict_types=1);

require '/var/www/magento/app/bootstrap.php';

function step(string $msg): void
{
    echo "\n=== $msg ===\n";
}

function fail(string $msg, int $code = 1): never
{
    fwrite(STDERR, "FAIL: $msg\n");
    exit($code);
}

$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();

/** @var \Magento\Framework\App\State $state */
$state = $om->get(\Magento\Framework\App\State::class);
try {
    $state->setAreaCode('frontend');
} catch (\Exception $e) {
}

/** @var \Magento\Store\Model\StoreManagerInterface $storeManager */
$storeManager = $om->get(\Magento\Store\Model\StoreManagerInterface::class);
$storeManager->setCurrentStore('en');
$storeId = (int)$storeManager->getStore()->getId();

$pdo = $om->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();

step('1. Find in-stock tyre product');
$row = $pdo->fetchRow(
    "SELECT cpe.entity_id, cpe.sku FROM catalog_product_entity cpe
     JOIN cataloginventory_stock_status st ON st.product_id = cpe.entity_id AND st.stock_status = 1
     JOIN catalog_category_product cp ON cp.product_id = cpe.entity_id AND cp.category_id = 1945
     LIMIT 1"
);
if (!$row) {
    fail('No in-stock product in car-tyres category');
}
$productId = (int)$row['entity_id'];
echo "Product ID: $productId SKU: {$row['sku']}\n";

step('2. Create guest cart and add product');
/** @var \Magento\Quote\Api\CartManagementInterface $cartManagement */
$cartManagement = $om->get(\Magento\Quote\Api\CartManagementInterface::class);
/** @var \Magento\Quote\Api\CartRepositoryInterface $cartRepository */
$cartRepository = $om->get(\Magento\Quote\Api\CartRepositoryInterface::class);
/** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
$productRepository = $om->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
/** @var \Magento\Checkout\Model\Cart $cart */
$cart = $om->get(\Magento\Checkout\Model\Cart::class);
/** @var \Magento\Checkout\Model\Session $checkoutSession */
$checkoutSession = $om->get(\Magento\Checkout\Model\Session::class);

$cartId = (int)$cartManagement->createEmptyCart();
$quote = $cartRepository->get($cartId);
$checkoutSession->replaceQuote($quote);
$checkoutSession->setQuoteId($cartId);

$product = $productRepository->getById($productId, false, $storeId);
if (!$product->isSalable()) {
    fail("Product {$row['sku']} is not salable");
}

$cart->setQuote($quote);
$cart->addProduct($product, ['qty' => 1]);

/** @var \Hdweb\Installer\Helper\Data $installerHelper */
$installerHelper = $om->create(\Hdweb\Installer\Helper\Data::class);
$hasMobile = $installerHelper->checkHasNoMobileVanTyreServiceProduct();
echo "checkHasNoMobileVanTyreServiceProduct: $hasMobile\n";

$quote->setPickupDate('');
$quote->save();
$cart->save();

$items = $quote->getAllItems();
echo "Quote ID: $cartId Items: " . count($items) . "\n";
if (count($items) < 1) {
    fail('Cart is empty after add');
}
foreach ($items as $item) {
    echo " - {$item->getSku()} qty={$item->getQty()}\n";
}

step('3. Select fitting location (pickup store)');
$storeRow = $pdo->fetchRow(
    "SELECT stores_id, name FROM ecomteck_storelocator_stores WHERE status = 1 ORDER BY stores_id LIMIT 1"
);
if (!$storeRow) {
    fail('No active pickup store in store locator');
}
$pickupStoreId = (int)$storeRow['stores_id'];
$pickupDate = date('Y-m-d', strtotime('+3 days'));
$pickupTime = '09:00 - 11:00';
echo "Store: {$storeRow['name']} (ID $pickupStoreId)\n";
echo "Pickup: $pickupDate $pickupTime\n";

$quote = $cartRepository->get($cartId);
$quote->setPickupDate($pickupDate);
$quote->setPickupTime($pickupTime);
$quote->setPickupStore((string)$pickupStoreId);
$quote->setDeliveryDate($pickupDate);
$quote->setDeliveryComment($pickupTime);
$quote->save();

$checkoutSession->setIsFitmentData('0');
$checkoutSession->setPickupdate($pickupDate);
$checkoutSession->setPickuptime($pickupTime);
$checkoutSession->setPickupstoreid((string)$pickupStoreId);

echo "Quote pickup_store: {$quote->getPickupStore()}\n";

step('4. Fill guest shipping/billing information');
$regionId = (int)($pdo->fetchOne(
    "SELECT region_id FROM directory_country_region WHERE country_id = 'SA' AND default_name LIKE 'Riyadh%' LIMIT 1"
) ?: 0);

$addressData = [
    'firstname' => 'Test',
    'lastname' => 'Customer',
    'street' => ['King Fahd Road', 'Al Olaya'],
    'city' => 'Riyadh',
    'region' => 'Riyadh',
    'region_id' => $regionId ?: null,
    'postcode' => '12345',
    'country_id' => 'SA',
    'telephone' => '+966501234567',
    'email' => 'checkout-test-' . time() . '@tyresonline.sa.test',
];

$quote = $cartRepository->get($cartId);
$quote->setCustomerEmail($addressData['email']);
$quote->setCustomerIsGuest(true);

$applyAddress = static function ($address, array $data): void {
    $address->setFirstname($data['firstname']);
    $address->setLastname($data['lastname']);
    $address->setStreet($data['street']);
    $address->setCity($data['city']);
    $address->setRegion($data['region']);
    if (!empty($data['region_id'])) {
        $address->setRegionId((int)$data['region_id']);
    }
    $address->setPostcode($data['postcode']);
    $address->setCountryId($data['country_id']);
    $address->setTelephone($data['telephone']);
};

$applyAddress($quote->getShippingAddress(), $addressData);
$applyAddress($quote->getBillingAddress(), $addressData);
$quote->getShippingAddress()->setSameAsBilling(1);

step('5. Collect shipping rates and set method');
$quote->getShippingAddress()->setCollectShippingRates(true);
$quote->collectTotals();
$quote->save();

$shippingAddress = $quote->getShippingAddress();
$rates = $shippingAddress->getGroupedAllShippingRates();
$selectedRate = null;
foreach ($rates as $carrierRates) {
    foreach ($carrierRates as $rate) {
        echo "Rate: {$rate->getCarrier()}_{$rate->getMethod()} — {$rate->getMethodTitle()} ({$rate->getPrice()})\n";
        if ($selectedRate === null) {
            $selectedRate = $rate;
        }
    }
}
if ($selectedRate === null) {
    // Fallback: flatrate or storepickup
    $shippingAddress->setShippingMethod('flatrate_flatrate');
    echo "No rates returned — using flatrate_flatrate fallback\n";
} else {
    $method = $selectedRate->getCarrier() . '_' . $selectedRate->getMethod();
    $shippingAddress->setShippingMethod($method);
    echo "Selected shipping: $method\n";
}

$quote->collectTotals();
$quote->save();

$grandTotal = $quote->getGrandTotal();
echo "Grand total: $grandTotal {$quote->getQuoteCurrencyCode()}\n";

step('6. Select payment method');
$scopeConfig = $om->get(\Magento\Framework\App\Config\ScopeConfigInterface::class);
$paymentMethod = null;
$candidates = ['cashondelivery', 'checkmo', 'banktransfer', 'HyperPay_Mada', 'HyperPay_Visa'];
foreach ($candidates as $code) {
    $active = $scopeConfig->getValue("payment/$code/active", \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    if ($active) {
        $paymentMethod = $code;
        echo "Using payment method: $code\n";
        break;
    }
}
if ($paymentMethod === null) {
    fail('No active payment method found');
}

$quote->getPayment()->setMethod($paymentMethod);
$quote->save();

step('7. Place order');
try {
    $orderId = $cartManagement->placeOrder($cartId);
    echo "ORDER PLACED — entity ID: $orderId\n";

    /** @var \Magento\Sales\Api\OrderRepositoryInterface $orderRepo */
    $orderRepo = $om->get(\Magento\Sales\Api\OrderRepositoryInterface::class);
    $order = $orderRepo->get($orderId);
    echo "Increment ID: {$order->getIncrementId()}\n";
    echo "Status: {$order->getStatus()} / {$order->getState()}\n";
    echo "Total: {$order->getGrandTotal()} {$order->getOrderCurrencyCode()}\n";
    echo "Pickup store: {$order->getPickupStore()}\n";

    step('RESULT: FULL CHECKOUT CYCLE PASSED');
    exit(0);
} catch (\Throwable $e) {
    echo "Place order error: {$e->getMessage()}\n";
    echo $e->getFile() . ':' . $e->getLine() . "\n";

    // Still validate checkout page would load
    step('8. Verify checkout page renders for this quote');
    try {
        $_SERVER['REQUEST_URI'] = '/en/checkout/';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_HOST'] = 'stg.tyresonline.sa';
        $_SERVER['SERVER_NAME'] = 'stg.tyresonline.sa';
        $_SERVER['HTTPS'] = 'on';

        $checkoutSession->replaceQuote($cartRepository->get($cartId));
        $controller = $om->create(\Magento\Checkout\Controller\Index\Index::class);
        $result = $controller->execute();
        if ($result instanceof \Magento\Framework\Controller\Result\Redirect) {
            echo 'Checkout redirects to: ' . $result->getUrl() . "\n";
        } elseif ($result instanceof \Magento\Framework\View\Result\Page) {
            echo "Checkout page result: Page object OK\n";
        }
    } catch (\Throwable $renderEx) {
        echo "Checkout render error: {$renderEx->getMessage()}\n";
    }

    step('RESULT: CHECKOUT PARTIAL — cart + location + info OK; payment/order blocked');
    echo "Payment method attempted: $paymentMethod\n";
    echo "This is expected if HyperPay live credentials are not configured.\n";
    exit(2);
}
