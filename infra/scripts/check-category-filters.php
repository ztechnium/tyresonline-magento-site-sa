<?php
require '/var/www/magento/app/bootstrap.php';
$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$state = $om->get(\Magento\Framework\App\State::class);
try {
    $state->setAreaCode('frontend');
} catch (\Exception $e) {
}

$storeManager = $om->get(\Magento\Store\Model\StoreManagerInterface::class);
$storeManager->setCurrentStore('en');
$categoryId = 1945;

$categoryRepo = $om->get(\Magento\Catalog\Api\CategoryRepositoryInterface::class);
$category = $categoryRepo->get($categoryId, $storeManager->getStore()->getId());

echo 'name=' . $category->getName() . PHP_EOL;
echo 'display_mode=' . $category->getDisplayMode() . PHP_EOL;
echo 'is_anchor=' . (int)$category->getIsAnchor() . PHP_EOL;
echo 'is_active=' . (int)$category->getIsActive() . PHP_EOL;

$layerResolver = $om->get(\Magento\Catalog\Model\Layer\Resolver::class);
$layerResolver->create(\Magento\Catalog\Model\Layer\Resolver::CATALOG_LAYER_CATEGORY);
$layer = $layerResolver->get();
$layer->setCurrentCategory($category);

$filters = $layer->getFilters();
echo 'filter_count=' . count($filters) . PHP_EOL;
foreach ($filters as $filter) {
    echo '  ' . $filter->getName() . ' items=' . $filter->getItemsCount() . PHP_EOL;
}

$navBlock = $om->create(\Magento\LayeredNavigation\Block\Navigation\Category::class);
$navBlock->setLayer($layer);
echo 'canShowBlock=' . ($navBlock->canShowBlock() ? '1' : '0') . PHP_EOL;
