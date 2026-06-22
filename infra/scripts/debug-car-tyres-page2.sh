#!/bin/bash
DB="-h tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com -u magento -puxaYMIQRwEU0AFl1Ck69LJnH tyresonline_sa"

mysql $DB -e "
SELECT a.attribute_code, v.value
FROM catalog_category_entity_int v
JOIN eav_attribute a ON v.attribute_id=a.attribute_id
WHERE v.entity_id=1945 AND v.store_id IN (0,1)
AND a.attribute_code IN ('display_mode','is_anchor','page_layout','custom_layout_update');
"

echo '=== try with explicit query params ==='
for url in \
  'https://stg.tyresonline.sa/en/all-tyres/car-tyres.html?product_list_limit=24' \
  'https://stg.tyresonline.sa/en/all-tyres/car-tyres.html?width=215' \
  'https://stg.tyresonline.sa/en/catalogsearch/result/?q=alpha'
do
  cnt=$(curl -s "$url" | grep -c 'product-item-info')
  empty=$(curl -s "$url" | grep -ci "can't find")
  echo "$url => items:$cnt empty:$empty"
done

echo '=== search product list block in html ==='
grep -oE 'block-category-list|category\.products|catalog\.category\.view|product_list|amasty.shopby|shopby' /tmp/car4.html | sort -u | head -20

echo '=== price slider json snippet ==='
grep -o 'jsonConfig[^;]*' /tmp/car4.html | head -1 | head -c 300

echo '=== check if products wrapper exists empty ==='
grep -A3 'products wrapper' /tmp/car4.html | head -10
grep -B2 -A5 'message info empty' /tmp/car4.html | head -20
