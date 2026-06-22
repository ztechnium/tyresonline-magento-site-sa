#!/bin/bash
cd /var/www/magento
sudo -u www-data php bin/magento cache:clean block_html full_page 2>&1
sudo -u www-data php bin/magento cache:disable block_html full_page 2>&1
sudo rm -rf var/page_cache/* var/view_preprocessed/* generated/code/Magento/Catalog/Block/* 2>/dev/null || true

curl -sI 'https://stg.tyresonline.sa/en/all-tyres/car-tyres.html?bust='$(date +%s) | grep -iE 'cache|age|x-magento'

curl -s 'https://stg.tyresonline.sa/en/all-tyres/car-tyres.html?bust='$(date +%s) -o /tmp/car5.html
echo "items: $(grep -c 'product-item-info' /tmp/car5.html)"
grep -i "can't find" /tmp/car5.html | head -1

# Check if ElasticSuite ListProduct is used
grep -r 'ListProduct' /var/www/magento/vendor/smile/elasticsuite/src/module-elasticsuite-catalog/Block --include='*.php' | head -5
grep -r 'ListProduct' /var/www/magento/app/code --include='*.xml' | head -10
