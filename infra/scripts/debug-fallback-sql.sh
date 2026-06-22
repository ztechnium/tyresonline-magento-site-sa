#!/bin/bash
cd /var/www/magento
cp app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/list.phtml /tmp/list.bak3
sed -i 's/\$collectionSize = max((int) \$_productCollection->getSize(), \$loadedItemsCount);/\$collectionSize = max((int) $_productCollection->getSize(), $loadedItemsCount);\necho "<!-- STORE ".$storeManager->getStore()->getId()." FB=".$loadedItemsCount." SQL=".htmlspecialchars(substr((string)$_productCollection->getSelect(),0,300))." -->";/' app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/list.phtml
sudo -u www-data php bin/magento cache:flush >/dev/null 2>&1
curl -s 'https://stg.tyresonline.sa/en/all-tyres/car-tyres.html?z=1' | grep -o '<!-- STORE[^>]*-->' | head -1
cp /tmp/list.bak3 app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/list.phtml
