#!/bin/bash
for url in \
  'https://stg.tyresonline.sa/en/all-tyres/car-tyres.html?product_list_limit=24' \
  'https://stg.tyresonline.sa/en/all-tyres/car-tyres.html'
do
  echo "=== $url ==="
  curl -s "$url" -o /tmp/p.html
  grep -c 'product-item-info' /tmp/p.html
  grep -c 'product name' /tmp/p.html
  grep -iE "can't find|message info empty" /tmp/p.html | head -2
  grep -oE 'toolbar-amount|items [0-9]+-[0-9]+|of [0-9]+' /tmp/p.html | head -5
  grep -o 'class="product-item[^"]*"' /tmp/p.html | head -5
  grep -i 'TYO-' /tmp/p.html | head -3
done

echo '=== CLI list block ==='
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
\$collection->setPageSize(5);
echo 'loaded count: ' . count(\$collection) . PHP_EOL;
foreach (\$collection as \$p) { echo \$p->getSku() . PHP_EOL; }
" 2>&1
