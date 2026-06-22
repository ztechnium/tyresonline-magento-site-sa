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
\$request = \$om->get(\Magento\Framework\App\Request\Http::class);
\$request->setPathInfo('/en/all-tyres/car-tyres.html');
\$request->setRequestUri('/en/all-tyres/car-tyres.html');
\$category = \$om->get(\Magento\Catalog\Model\CategoryFactory::class)->create()->load(1945);
\$registry = \$om->get(\Magento\Framework\Registry::class);
\$registry->register('current_category', \$category);
\$layer = \$om->get(\Magento\Catalog\Model\Layer\Resolver::class)->get();
\$layer->setCurrentCategory(\$category);
\$layout = \$om->get(\Magento\Framework\View\LayoutInterface::class);
\$list = \$layout->createBlock(\Hdweb\Tyrefinder\Block\Product\ListProduct::class);
\$list->setCategoryId(1945);
\$col = \$list->getLoadedProductCollection();
echo 'Hdweb list size: '.\$col->getSize().PHP_EOL;
echo 'Hdweb list items: '.count(\$col->getItems()).PHP_EOL;
echo 'width param: ' . var_export(\$request->getParam('width'), true) . PHP_EOL;
echo 'SQL tail: ' . substr(\$col->getSelect()->__toString(), -400) . PHP_EOL;
" 2>&1
