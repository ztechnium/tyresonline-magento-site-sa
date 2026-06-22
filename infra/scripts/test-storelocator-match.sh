#!/bin/bash
cd /var/www/magento
sudo -u www-data php -r "
require 'app/bootstrap.php';
\$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, \$_SERVER);
\$om = \$bootstrap->getObjectManager();
\$state = \$om->get(\Magento\Framework\App\State::class);
try { \$state->setAreaCode('frontend'); } catch (\Exception \$e) {}
\$storeManager = \$om->get(\Magento\Store\Model\StoreManagerInterface::class);
\$storeManager->setCurrentStore('en');

// Create guest cart with one tyre product
\$cart = \$om->get(\Magento\Checkout\Model\Cart::class);
\$product = \$om->get(\Magento\Catalog\Api\ProductRepositoryInterface::class)->get('TYO-01579DN-2025');
\$cart->truncate()->save();
\$cart->addProduct(\$product, 4)->save();

\$collectionFactory = \$om->get(\Ecomteck\StoreLocator\Model\ResourceModel\Stores\CollectionFactory::class);
\$collection = \$collectionFactory->create()->addActiveFilter();
\$quote = \$om->get(\Magento\Checkout\Model\Session::class)->getQuote();
\$productIds = [];
\$allotemcategory = [];
\$repo = \$om->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
foreach (\$quote->getAllVisibleItems() as \$item) {
    \$productIds[] = \$item->getProductId();
    \$product = \$repo->getById(\$item->getProductId());
    \$allotemcategory = array_merge(\$allotemcategory, \$product->getCategoryIds());
}
\$collection->addProductsFilter(\$productIds);
\$nofitment = (int)\$om->get(\Magento\Framework\App\Config\ScopeConfigInterface::class)->getValue('installer/general/no_fitment_installer');
\$mobilevan = (int)\$om->get(\Magento\Framework\App\Config\ScopeConfigInterface::class)->getValue('installer/general/mobilevan_fitment_service_installer');
\$count = 0;
foreach (\$collection as \$stores) {
    \$data = \$stores->getData();
    if ((int)\$data['stores_id'] === \$nofitment || (int)\$data['stores_id'] === \$mobilevan) continue;
    \$installercategory = explode(',', \$data['category']);
    if (!count(array_intersect(\$allotemcategory, \$installercategory))) continue;
    \$count++;
}
echo 'cart categories: '.implode(',', array_unique(\$allotemcategory)).PHP_EOL;
echo 'matching installers: '.\$count.PHP_EOL;
" 2>&1
