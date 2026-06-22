#!/bin/bash
LIST=/var/www/magento/app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/list.phtml
if ! grep -q 'KSA-DEBUG-MARKER' "$LIST"; then
  sed -i '1i<?php echo "<!-- KSA-DEBUG-MARKER-v2 -->"; ?>' "$LIST"
fi
rm -rf /var/www/magento/var/view_preprocessed/*
cd /var/www/magento
sudo -u www-data php bin/magento cache:flush
curl -s 'https://stg.tyresonline.sa/en/all-tyres/car-tyres.html?m=v2' | grep -o 'KSA-DEBUG-MARKER[^<]*' | head -1
