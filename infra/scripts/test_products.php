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

$layerResolver = $om->get(\Magento\Catalog\Model\Layer\Resolver::class);
$layerResolver->create(\Magento\Catalog\Model\Layer\Resolver::CATALOG_LAYER_CATEGORY);
$category = $om->get(\Magento\Catalog\Model\CategoryFactory::class)->create()->load(1945);
$layerResolver->get()->setCurrentCategory($category);
$collection = $layerResolver->get()->getProductCollection();
$collection->setPageSize(12)->setCurPage(1);
$listingHelper = $om->get(\Hdweb\Tyrefinder\Helper\Productlisting::class);

$i = 0;
foreach ($collection as $product) {
    $i++;
    $id = $product->getId();
    $name = $product->getName();
    try {
        $qty = $listingHelper->getSalebleQty($id);
        $price = $listingHelper->getSet1price($product);
        $brand = $listingHelper->getBrandDetails($product->getData('mgs_brand'));
        $brandName = $brand ? $brand->getName() : 'N/A';
        echo "#$i ID=$id SKU={$product->getSku()} qty=$qty price=$price brand=$brandName OK\n";
    } catch (\Throwable $e) {
        echo "#$i ID=$id SKU={$product->getSku()} ERROR: " . $e->getMessage() . "\n";
    }
}
