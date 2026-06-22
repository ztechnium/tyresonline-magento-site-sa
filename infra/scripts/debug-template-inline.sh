#!/bin/bash
grep -oE 'category-[a-z0-9-]+|categorypath-[^" ]+' /tmp/car5.html | head -10
grep -o 'body[^>]*class="[^"]*"' /tmp/car5.html | head -3

# Full front controller test with debug in template
cd /var/www/magento
# Patch template temporarily with debug comment
cp app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/list.phtml /tmp/list.phtml.bak
sed -i 's/<?php if (\$loadedItemsCount === 0/<?php echo "<!-- DEBUG size=\$collectionSize loaded=\$loadedItemsCount hasTyre=".($hasTyreSearch?"1":"0")." path=\$requestPath -->"; ?><?php if ($loadedItemsCount === 0/' app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/list.phtml

curl -s 'https://stg.tyresonline.sa/en/all-tyres/car-tyres.html?dbg='$(date +%s) | grep 'DEBUG size'
cp /tmp/list.phtml.bak app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/list.phtml
