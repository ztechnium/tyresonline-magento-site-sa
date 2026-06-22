#!/bin/bash
cd /var/www/magento

echo '=== grep theme/modules for empty listing logic ==='
grep -rn "can't find products\|matching the selection\|category-product-actions" app/design vendor --include='*.phtml' --include='*.xml' --include='*.php' 2>/dev/null | head -25

echo '=== render ListProduct block via layout ==='
sudo -u www-data php -r "
require 'app/bootstrap.php';
\$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, \$_SERVER);
\$om = \$bootstrap->getObjectManager();
\$state = \$om->get(\Magento\Framework\App\State::class);
try { \$state->setAreaCode('frontend'); } catch (\Exception \$e) {}
\$storeManager = \$om->get(\Magento\Store\Model\StoreManagerInterface::class);
\$storeManager->setCurrentStore('en');
\$registry = \$om->get(\Magento\Framework\Registry::class);
\$category = \$om->get(\Magento\Catalog\Model\CategoryFactory::class)->create()->load(1945);
\$registry->register('current_category', \$category);
\$registry->register('current_category_id', 1945);
\$layout = \$om->get(\Magento\Framework\View\LayoutInterface::class);
\$block = \$layout->createBlock(\Magento\Catalog\Block\Product\ListProduct::class, 'test.list');
\$block->setTemplate('Magento_Catalog::product/list.phtml');
\$html = \$block->toHtml();
echo 'list html bytes: '.strlen(\$html).PHP_EOL;
echo 'product-item-info: '.substr_count(\$html,'product-item-info').PHP_EOL;
echo substr(\$html,0,500).PHP_EOL;
" 2>&1
