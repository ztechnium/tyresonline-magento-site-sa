#!/bin/bash
DB="-h tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com -u magento -puxaYMIQRwEU0AFl1Ck69LJnH tyresonline_sa"

echo '=== WEBSITE ASSIGNMENT sample car-tyres products ==='
mysql $DB -e "
SELECT COUNT(DISTINCT cp.product_id) AS in_category,
       COUNT(DISTINCT pw.product_id) AS on_website1,
       COUNT(DISTINCT CASE WHEN st.value=1 THEN cp.product_id END) AS enabled,
       COUNT(DISTINCT CASE WHEN ss.stock_status=1 THEN cp.product_id END) AS in_stock
FROM catalog_category_product cp
LEFT JOIN catalog_product_website pw ON pw.product_id=cp.product_id AND pw.website_id=1
LEFT JOIN catalog_product_entity_int st ON st.entity_id=cp.product_id AND st.attribute_id=(SELECT attribute_id FROM eav_attribute WHERE attribute_code='status' AND entity_type_id=4) AND st.store_id=0
LEFT JOIN cataloginventory_stock_status ss ON ss.product_id=cp.product_id AND ss.website_id=1
WHERE cp.category_id=1945;
"

echo '=== MSI salable for website 1 ==='
mysql $DB -e "
SELECT COUNT(DISTINCT cp.product_id) salable
FROM catalog_category_product cp
JOIN inventory_stock_3 s ON s.sku=(SELECT sku FROM catalog_product_entity WHERE entity_id=cp.product_id)
WHERE cp.category_id=1945 AND s.is_salable=1;
" 2>/dev/null || echo 'MSI query failed'

echo '=== hideaddtocart / special attrs ==='
mysql $DB -e "SELECT attribute_code FROM eav_attribute WHERE attribute_code IN ('hideaddtocart','am_hide_from_category') AND entity_type_id=4;"

echo '=== recent exception log ==='
tail -5 /var/www/magento/var/log/exception.log 2>/dev/null

echo '=== page filters/amshopby ==='
curl -s 'https://stg.tyresonline.sa/en/all-tyres/car-tyres.html' | grep -iE 'amshopby|applied|filter-current|data-amshopby|shopby' | head -15

echo '=== try without layered nav - direct search ==='
curl -s 'https://stg.tyresonline.sa/en/catalogsearch/result/?q=tyre' -o /tmp/search.html
grep -c 'product-item-info' /tmp/search.html
grep -i "can't find" /tmp/search.html | head -1

echo '=== ElasticSuite indices settings ==='
cd /var/www/magento
sudo -u www-data php bin/magento config:show smile_elasticsuite_core_base_settings 2>/dev/null | head -20
