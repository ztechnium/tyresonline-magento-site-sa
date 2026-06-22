#!/bin/bash
cd /var/www/magento
sudo -u www-data php -r "
\$_SERVER['HTTP_HOST']='stg.tyresonline.sa';
\$_SERVER['REQUEST_URI']='/en/all-tyres/car-tyres.html';
\$_SERVER['SCRIPT_NAME']='/index.php';
\$_SERVER['SCRIPT_FILENAME']='/var/www/magento/pub/index.php';
\$_SERVER['DOCUMENT_ROOT']='/var/www/magento/pub';
\$_SERVER['REQUEST_METHOD']='GET';
\$_SERVER['SERVER_PORT']='443';
\$_SERVER['HTTPS']='on';
require 'app/bootstrap.php';
\$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, \$_SERVER);
\$om = \$bootstrap->getObjectManager();
\$state = \$om->get(\Magento\Framework\App\State::class);
try { \$state->setAreaCode('frontend'); } catch (\Exception \$e) {}
\$storeManager = \$om->get(\Magento\Store\Model\StoreManagerInterface::class);
\$storeManager->setCurrentStore('en');

// Resolve URL like front controller
\$url = \$om->get(\Magento\UrlRewrite\Model\UrlFinderInterface::class);
\$rewrite = \$url->findOneByData(['request_path'=>'all-tyres/car-tyres.html', 'store_id'=>1]);
echo 'rewrite target: '.(\$rewrite ? \$rewrite->getTargetPath() : 'none').PHP_EOL;

\$categoryId = 1945;
\$category = \$om->get(\Magento\Catalog\Model\CategoryFactory::class)->create()->load(\$categoryId);
echo 'cat id: '.\$category->getId().' url_path: '.\$category->getUrlPath().' url_key: '.\$category->getUrlKey().PHP_EOL;

\$registry = \$om->get(\Magento\Framework\Registry::class);
\$registry->register('current_category', \$category);
\$layerResolver = \$om->get(\Magento\Catalog\Model\Layer\Resolver::class);
\$layer = \$layerResolver->get();
\$layer->setCurrentCategory(\$category);
\$col = \$layer->getProductCollection();
echo 'layer size: '.\$col->getSize().' items: '.count(\$col->getItems()).PHP_EOL;

// Hdweb block like on page
\$layout = \$om->get(\Magento\Framework\View\LayoutFactory::class)->create();
\$layout->getUpdate()->load('catalog_category_view');
\$layout->generateXml();
\$layout->generateElements();
\$block = \$layout->getBlock('category.products.list');
if (\$block) {
  echo 'block class: '.get_class(\$block).PHP_EOL;
  \$bcol = \$block->getLoadedProductCollection();
  echo 'block size: '.\$bcol->getSize().' items: '.count(\$bcol->getItems()).PHP_EOL;
} else {
  echo 'no category.products.list block'.PHP_EOL;
}
" 2>&1
