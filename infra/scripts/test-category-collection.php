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
$storeManager->setCurrentStore(1);
$cat = $om->get(\Magento\Catalog\Model\CategoryRepository::class)->get(1945, 1);
$layer = $om->get(\Magento\Catalog\Model\Layer\Resolver::class)->get();
$layer->setCurrentCategory($cat);
$c = $layer->getProductCollection();
echo 'store_id=' . $storeManager->getStore()->getId() . PHP_EOL;
echo 'website_id=' . $storeManager->getStore()->getWebsiteId() . PHP_EOL;
echo 'collection_size=' . $c->getSize() . PHP_EOL;
echo 'sql=' . $c->getSelect() . PHP_EOL;
