#!/bin/bash
curl -s 'https://stg.tyresonline.sa/en/all-tyres/car-tyres.html' -o /tmp/car4.html

echo '=== sizes ==='
wc -c /tmp/car4.html
grep -c 'product-item' /tmp/car4.html
grep -c 'product-item-info' /tmp/car4.html
grep -c 'Alpha ' /tmp/car4.html

echo '=== empty msg ==='
grep -iE "can't find|matching the selection|message info empty" /tmp/car4.html

echo '=== listing block ==='
grep -iE 'products-grid|products-list|category-products|toolbar-number|product-items' /tmp/car4.html | head -10

echo '=== ajax / infinite ==='
grep -iE 'ajax|infinite|elasticsuite|load-more|amscroll' /tmp/car4.html | head -15

echo '=== recent system/exception ==='
tail -3 /var/www/magento/var/log/system.log 2>/dev/null
tail -1 /var/www/magento/var/log/exception.log 2>/dev/null | head -c 400

echo '=== trigger page from CLI with debug ==='
cd /var/www/magento
sudo -u www-data php -r "
require 'app/bootstrap.php';
\$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, \$_SERVER);
\$om = \$bootstrap->getObjectManager();
\$state = \$om->get(\Magento\Framework\App\State::class);
try { \$state->setAreaCode('frontend'); } catch (\Exception \$e) {}
\$storeManager = \$om->get(\Magento\Store\Model\StoreManagerInterface::class);
\$storeManager->setCurrentStore('en');
\$category = \$om->get(\Magento\Catalog\Model\CategoryFactory::class)->create()->load(1945);
\$layer = \$om->get(\Magento\Catalog\Model\Layer\Resolver::class)->get();
\$layer->setCurrentCategory(\$category);
\$collection = \$layer->getProductCollection();
echo 'collection size: ' . \$collection->getSize() . PHP_EOL;
echo 'sql: ' . substr(\$collection->getSelect()->__toString(), 0, 500) . PHP_EOL;
" 2>&1
