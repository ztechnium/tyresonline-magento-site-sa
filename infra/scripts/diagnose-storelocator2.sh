#!/bin/bash
DB="-h tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com -u magento -puxaYMIQRwEU0AFl1Ck69LJnH tyresonline_sa"

echo '=== installer config ==='
mysql $DB -e "
SELECT path, value FROM core_config_data
WHERE path LIKE 'installer/%' OR path LIKE 'ecomteck_storepickup/%' OR path LIKE 'ecomteck_storelocator/map%'
ORDER BY path;
"

echo '=== store category assignments sample ==='
mysql $DB -e "
SELECT stores_id, name, status, country, category
FROM ecomteck_storelocator_stores
WHERE status=1
LIMIT 15;
"

echo '=== distinct category values on stores ==='
mysql $DB -e "
SELECT category, COUNT(*) c FROM ecomteck_storelocator_stores WHERE status=1 GROUP BY category ORDER BY c DESC LIMIT 15;
"

echo '=== KSA car-tyres category id ==='
mysql $DB -e "
SELECT entity_id, v.value url_key FROM catalog_category_entity cce
JOIN catalog_category_entity_varchar v ON v.entity_id=cce.entity_id
AND v.attribute_id=(SELECT attribute_id FROM eav_attribute WHERE attribute_code='url_key' AND entity_type_id=3)
AND v.store_id=0 WHERE v.value='car-tyres';
"

echo '=== test ajax endpoint ==='
curl -s 'https://stg.tyresonline.sa/en/storelocator/ajax/stores?quote=1' -H 'Cookie: PHPSESSID=test' | head -c 500
echo
