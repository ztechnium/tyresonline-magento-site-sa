#!/usr/bin/env php
<?php
/**
 * Verify cart fitting locations: stores AJAX returns installers when cart has tyres.
 * Run on KSA staging: sudo -u www-data php infra/scripts/verify-cart-fitting-locations.php
 */
declare(strict_types=1);

require '/var/www/magento/app/bootstrap.php';

function ok(string $msg): void { echo "OK: $msg\n"; }
function fail(string $msg): never { fwrite(STDERR, "FAIL: $msg\n"); exit(1); }

$om = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER)->getObjectManager();
$state = $om->get(\Magento\Framework\App\State::class);
try { $state->setAreaCode('frontend'); } catch (\Exception $e) {}

$storeManager = $om->get(\Magento\Store\Model\StoreManagerInterface::class);
$storeManager->setCurrentStore('en');

$jsPath = BP . '/app/code/Ecomteck/StoreLocator/view/frontend/web/js/ecomteck_storelocator.js';
$js = file_get_contents($jsPath);
if (strpos($js, 'ecomteck_storelocator') !== false && preg_match("/'ecomteck_storelocator'/", $js)) {
    fail('ecomteck_storelocator.js still has circular require dependency');
}
if (strpos($js, 'loadGoogleMaps') === false) {
    fail('ecomteck_storelocator.js missing loadGoogleMaps');
}
ok('ecomteck_storelocator.js has loadGoogleMaps and no circular require');

$phtmlPath = BP . '/app/design/frontend/Hditsol/tyresonline/Ecomteck_StoreLocator/templates/storelocator/right.phtml';
$phtml = file_get_contents($phtmlPath);
if (strpos($phtml, 'whenGoogleMapsReady') === false) {
    fail('right.phtml missing whenGoogleMapsReady');
}
if (strpos($phtml, 'maps.googleapis.com') === false) {
    fail('right.phtml missing Google Maps script tag');
}
ok('right.phtml has Google Maps init helpers');

$helperPath = BP . '/app/code/Hdweb/Addattribute/Helper/Productdetails.php';
$helperSrc = file_get_contents($helperPath);
if (!preg_match('/protected\s+\$_productloader;/', $helperSrc)) {
    fail('Productdetails.php missing protected $_productloader');
}
ok('Productdetails.php has PHP 8.2 property declarations');

$cartManagement = $om->get(\Magento\Quote\Api\CartManagementInterface::class);
$cartRepository = $om->get(\Magento\Quote\Api\CartRepositoryInterface::class);
$productRepository = $om->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
$cart = $om->get(\Magento\Checkout\Model\Cart::class);
$checkoutSession = $om->get(\Magento\Checkout\Model\Session::class);
$pdo = $om->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();

$productId = 0;
foreach ([6954, 6953] as $candidateId) {
    try {
        $candidate = $productRepository->getById($candidateId);
        if ($candidate->isSalable()) {
            $productId = $candidateId;
            break;
        }
    } catch (\Exception $e) {
        continue;
    }
}
if (!$productId) {
    $productId = (int)$pdo->fetchOne(
        "SELECT cpe.entity_id FROM catalog_product_entity cpe
         JOIN cataloginventory_stock_status st ON st.product_id = cpe.entity_id AND st.stock_status = 1
         JOIN catalog_category_product cp ON cp.product_id = cpe.entity_id AND cp.category_id = 1945
         LIMIT 1"
    );
}
if (!$productId) {
    fail('No tyre product available for cart test');
}

$cartId = (int)$cartManagement->createEmptyCart();
$quote = $cartRepository->get($cartId);
$checkoutSession->replaceQuote($quote);
$product = $productRepository->getById($productId);
if (!$product->isSalable()) {
    fail("Product $productId is not salable");
}
$cart->setQuote($quote);
$cart->addProduct($product, ['qty' => 1]);
$cart->save();
$checkoutSession->replaceQuote($cartRepository->get($cartId));

$collectionFactory = $om->get(\Ecomteck\StoreLocator\Model\ResourceModel\Stores\CollectionFactory::class);
$collection = $collectionFactory->create();
$collection->addActiveFilter();
$collection->addFieldToFilter('store_id', (string)$storeManager->getStore()->getId());

$allotemcategory = [];
foreach ($quote->getAllVisibleItems() as $item) {
    $p = $productRepository->getById($item->getProductId());
    $allotemcategory = array_merge($allotemcategory, $p->getCategoryIds());
}
$collection->addProductsFilter([$productId]);

$scopeConfig = $om->get(\Magento\Framework\App\Config\ScopeConfigInterface::class);
$nofitmentInstallerId = $scopeConfig->getValue('installer/general/no_fitment_installer');
$mobileVanfittingInstallerId = $scopeConfig->getValue('installer/general/mobilevan_fitment_service_installer');

$matched = 0;
foreach ($collection as $stores) {
    $data = $stores->getData();
    if ($data['stores_id'] == $nofitmentInstallerId || $data['stores_id'] == $mobileVanfittingInstallerId) {
        continue;
    }
    $installercategory = explode(',', (string)$data['category']);
    if (count(array_intersect($allotemcategory, $installercategory))) {
        $matched++;
    }
}
if ($matched < 1) {
    fail("No fitting stores matched cart product categories (got $matched)");
}
ok("Stores filter returns $matched installers for cart product $productId");

$block = $om->create(\Ecomteck\StoreLocator\Block\StoreLocator::class);
$block->setTemplate('Ecomteck_StoreLocator::storelocator/right.phtml');
$html = $block->toHtml();
if (strpos($html, 'class="list allInstaller') === false) {
    fail('Rendered right.phtml missing allInstaller list container');
}
if (strpos($html, 'ecomteck_storelocator') === false) {
    fail('Rendered right.phtml missing store locator init');
}
if (!preg_match('/allInstallerlocations = (\[[^\]]+\])/', $html, $m) || $m[1] === '[]') {
    fail('allInstallerForMap is empty in rendered template');
}
ok('Rendered fitting location modal includes store list markup and map markers');

echo "\nAll cart fitting location checks passed.\n";
exit(0);
