#!/bin/bash
cd /var/www/magento
cp app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/list.phtml /tmp/list.bak
# insert debug right after category registry line (line 58 area)
awk '/registry\('"'"'current_category'"'"'\)/{print; print "echo \"<!-- CATID \".($category?(int)$category->getId():\"NULL\").\" -->\";"; next}1' app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/list.phtml > /tmp/list.phtml.new
cp /tmp/list.phtml.new app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/list.phtml
sudo -u www-data php bin/magento cache:flush >/dev/null 2>&1
curl -s 'https://stg.tyresonline.sa/en/all-tyres/car-tyres.html?x='$(date +%s) | grep -o 'CATID [^ ]*'
cp /tmp/list.bak app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/list.phtml
