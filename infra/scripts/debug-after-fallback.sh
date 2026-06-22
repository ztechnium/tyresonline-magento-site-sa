#!/bin/bash
DB="-h tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com -u magento -puxaYMIQRwEU0AFl1Ck69LJnH tyresonline_sa"
mysql $DB -e "SELECT entity_id, path, children_count FROM catalog_category_flat WHERE entity_id IN (1944,1945) AND store_id=1;"

cd /var/www/magento
cp app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/list.phtml /tmp/list.bak2
sed -i 's/\$collectionSize = max((int) \$_productCollection->getSize(), \$loadedItemsCount);/\$collectionSize = max((int) $_productCollection->getSize(), $loadedItemsCount);\necho "<!-- AFTER_FB loaded=$loadedItemsCount size=$collectionSize -->";/' app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/list.phtml
sudo -u www-data php bin/magento cache:flush >/dev/null 2>&1
curl -s 'https://stg.tyresonline.sa/en/all-tyres/car-tyres.html?y='$(date +%s) | grep -oE 'AFTER_FB[^<]+|DEBUG[^<]+' | head -5
cp /tmp/list.bak2 app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/list.phtml
