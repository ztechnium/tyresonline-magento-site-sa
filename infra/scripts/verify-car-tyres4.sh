#!/bin/bash
DB="-h tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com -u magento -puxaYMIQRwEU0AFl1Ck69LJnH tyresonline_sa"

echo '=== per-store category index ==='
mysql $DB -e "SELECT category_id, COUNT(*) c FROM catalog_category_product_index_store1 WHERE category_id IN (1944,1945,2) GROUP BY category_id;"
mysql $DB -e "SELECT COUNT(*) total FROM catalog_category_product_index_store1;"

echo '=== nested category query on NEW index ==='
curl -s 'http://127.0.0.1:9200/satyresonline2_en_catalog_product/_search' -H 'Content-Type: application/json' -d '{"size":0,"query":{"nested":{"path":"category","query":{"term":{"category.category_id":1945}}}}}' | python3 -m json.tool

echo '=== flush all caches ==='
cd /var/www/magento
sudo -u www-data php bin/magento cache:flush 2>&1 | tail -3
sudo rm -rf var/page_cache/* var/view_preprocessed/* 2>/dev/null || true

echo '=== fresh page fetch ==='
curl -s -H 'Cache-Control: no-cache' 'https://stg.tyresonline.sa/en/all-tyres/car-tyres.html' -o /tmp/car2.html
grep -c 'product-item-info' /tmp/car2.html
grep -iE "can't find|matching the selection|toolbar-amount" /tmp/car2.html | head -5
grep -oP 'toolbar-amount[^<]*<span[^>]*>[^<]+' /tmp/car2.html | head -3
