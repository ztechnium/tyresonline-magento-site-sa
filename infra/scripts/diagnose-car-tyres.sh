#!/bin/bash
set -euo pipefail
DB="-h tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com -u magento -puxaYMIQRwEU0AFl1Ck69LJnH tyresonline_sa"

echo '=== CATEGORIES ==='
mysql $DB -e "
SELECT cce.entity_id,cce.path,cce.level,v.value AS url_key,n.value AS name
FROM catalog_category_entity cce
LEFT JOIN catalog_category_entity_varchar v ON v.entity_id=cce.entity_id AND v.attribute_id=(SELECT attribute_id FROM eav_attribute WHERE attribute_code='url_key' AND entity_type_id=3) AND v.store_id=0
LEFT JOIN catalog_category_entity_varchar n ON n.entity_id=cce.entity_id AND n.attribute_id=(SELECT attribute_id FROM eav_attribute WHERE attribute_code='name' AND entity_type_id=3) AND n.store_id=0
WHERE v.value IN ('car-tyres','all-tyres','tyres') OR cce.entity_id IN (2,3)
ORDER BY cce.entity_id;
"

echo '=== PRODUCTS IN car-tyres (1945) ==='
mysql $DB -e "
SELECT COUNT(*) AS linked FROM catalog_category_product WHERE category_id=1945;
SELECT COUNT(*) AS enabled_visible FROM catalog_category_product cp
JOIN catalog_product_entity_int st ON st.entity_id=cp.product_id AND st.attribute_id=(SELECT attribute_id FROM eav_attribute WHERE attribute_code='status' AND entity_type_id=4) AND st.store_id=0 AND st.value=1
JOIN catalog_product_entity_int vis ON vis.entity_id=cp.product_id AND vis.attribute_id=(SELECT attribute_id FROM eav_attribute WHERE attribute_code='visibility' AND entity_type_id=4) AND vis.store_id=0 AND vis.value IN (2,3,4)
WHERE cp.category_id=1945;
"

echo '=== TOP CATEGORIES BY PRODUCT COUNT ==='
mysql $DB -e "
SELECT cp.category_id, v.value AS url_key, COUNT(*) c
FROM catalog_category_product cp
LEFT JOIN catalog_category_entity_varchar v ON v.entity_id=cp.category_id AND v.attribute_id=(SELECT attribute_id FROM eav_attribute WHERE attribute_code='url_key' AND entity_type_id=3) AND v.store_id=0
GROUP BY cp.category_id, v.value ORDER BY c DESC LIMIT 15;
"

echo '=== SAMPLE PRODUCT category_ids IN OPENSEARCH ==='
curl -s "http://127.0.0.1:9200/satyresonline2_product_1_v4/_search" -H 'Content-Type: application/json' -d '{"size":3,"_source":["sku","category_ids","stock.is_in_stock"]}' | python3 -m json.tool 2>/dev/null | head -60

echo '=== OPENSEARCH HIT COUNT category 1945 ==='
curl -s "http://127.0.0.1:9200/satyresonline2_product_1_v4/_search" -H 'Content-Type: application/json' -d '{"size":0,"query":{"terms":{"category_ids":["1945"]}}}' 

echo
echo '=== INDEXER STATE ==='
cd /var/www/magento
sudo -u www-data php bin/magento indexer:status 2>&1 | head -20
