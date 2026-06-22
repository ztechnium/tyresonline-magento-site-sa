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

// Simulate layer collection from block
\$category = \$om->get(\Magento\Catalog\Model\CategoryFactory::class)->create()->load(1945);
\$registry = \$om->get(\Magento\Framework\Registry::class);
\$registry->register('current_category', \$category);
\$layer = \$om->get(\Magento\Catalog\Model\Layer\Resolver::class)->get();
\$layer->setCurrentCategory(\$category);
\$blockCollection = \$layer->getProductCollection();
echo 'block collection size: ' . \$blockCollection->getSize() . PHP_EOL;
echo 'block items count: ' . count(\$blockCollection->getItems()) . PHP_EOL;

// Fallback collection from template
\$collectionFactory = \$om->get(\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory::class);
\$fallback = \$collectionFactory->create()
    ->addAttributeToSelect(['name','small_image','thumbnail','price','special_price','url_key'])
    ->addCategoriesFilter(['in' => [1945]])
    ->addStoreFilter((int)\$storeManager->getStore()->getId())
    ->addAttributeToFilter('status', 1)
    ->addAttributeToFilter('visibility', ['in' => [2,3,4]])
    ->setCurPage(1)
    ->setPageSize(24);
echo 'fallback size: ' . \$fallback->getSize() . PHP_EOL;
echo 'fallback items: ' . count(\$fallback->getItems()) . PHP_EOL;
foreach (\$fallback as \$p) { echo \$p->getSku().PHP_EOL; break; }

// ElasticSuite collection via ListProduct
\$layout = \$om->get(\Magento\Framework\View\LayoutInterface::class);
\$list = \$layout->createBlock(\Magento\Catalog\Block\Product\ListProduct::class);
\$list->setCategoryId(1945);
\$loaded = \$list->getLoadedProductCollection();
echo 'list loaded size: ' . \$loaded->getSize() . PHP_EOL;
echo 'list loaded items: ' . count(\$loaded->getItems()) . PHP_EOL;
" 2>&1
