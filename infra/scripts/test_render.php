<?php
use Magento\Framework\App\Bootstrap;

require '/var/www/magento/app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$state = $om->get(\Magento\Framework\App\State::class);
try {
    $state->setAreaCode('frontend');
} catch (\Exception $e) {
}

$storeManager = $om->get(\Magento\Store\Model\StoreManagerInterface::class);
$storeManager->setCurrentStore('en');

$category = $om->get(\Magento\Catalog\Model\CategoryFactory::class)->create()->load(1945);
$layerResolver = $om->get(\Magento\Catalog\Model\Layer\Resolver::class);
$layerResolver->create(\Magento\Catalog\Model\Layer\Resolver::CATALOG_LAYER_CATEGORY);
$layerResolver->get()->setCurrentCategory($category);
$collection = $layerResolver->get()->getProductCollection();
$collection->setPageSize(12)->setCurPage(1);

$listingHelper = $om->get(\Hdweb\Tyrefinder\Helper\Productlisting::class);
$imageHelper = $om->get(\Magento\Catalog\Helper\Image::class);
$coreHelper = $om->get(\Hdweb\Tyrefinder\Helper\Core::class);

$product = null;
foreach ($collection as $p) {
    $product = $p;
    break;
}

echo "Testing product {$product->getId()} {$product->getSku()}\n";

$checks = [
    'display_name' => fn() => $product->getResource()->getAttribute('display_name')->getFrontend()->getValue($product),
    'image' => fn() => $imageHelper->init($product, 'category_page_grid')->getUrl(),
    'mediaGallery' => fn() => $product->getMediaGalleryImages() ? $product->getMediaGalleryImages()->getSize() : 0,
    'isAnyRuleExist' => fn() => count($listingHelper->isAnyRuleExist($product)),
    'speed_index' => fn() => $listingHelper->getAttributeValue($product, 'speed_index'),
    'formatSpeedRating' => fn() => $coreHelper->formatSpeedRating('V'),
    'getTyreSize' => fn() => $listingHelper->getTyreSize($product, true),
    'tabby_method' => fn() => $product->getResource()->getAttribute('tabby_method')->getFrontend()->getValue($product),
];

foreach ($checks as $name => $fn) {
    try {
        $result = $fn();
        echo "$name: OK (" . substr((string)$result, 0, 80) . ")\n";
    } catch (\Throwable $e) {
        echo "$name: FAIL - " . $e->getMessage() . "\n";
    }
}

// Test CMS block render
echo "\nTesting wheel-protectors CMS block render...\n";
try {
    $filter = $om->get(\Magento\Widget\Model\Template\FilterEmulate::class);
    $content = '{{block class="Magento\\Framework\\View\\Element\\Template" name="wheel-protectors" template="Hdweb_Tyrefinder::wheel-protectors-products-and-after.phtml"}}';
    $html = $filter->filter($content);
    echo "wheel-protectors block: OK, len=" . strlen($html) . "\n";
} catch (\Throwable $e) {
    echo "wheel-protectors block: FAIL - " . $e->getMessage() . "\n";
}
