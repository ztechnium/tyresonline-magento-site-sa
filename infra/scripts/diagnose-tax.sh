#!/bin/bash
DB="-h tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com -u magento -puxaYMIQRwEU0AFl1Ck69LJnH tyresonline_sa"

echo '=== tax config ==='
mysql $DB -e "
SELECT path, value FROM core_config_data
WHERE path LIKE 'tax/%' OR path LIKE 'general/country%' OR path LIKE 'general/locale%'
ORDER BY path;
"

echo '=== tax rates ==='
mysql $DB -e "SELECT tax_calculation_rate_id, code, tax_country_id, tax_region_id, rate, tax_postcode FROM tax_calculation_rate;"

echo '=== tax rules ==='
mysql $DB -e "
SELECT r.tax_calculation_rule_id, r.code, r.tax_rate_id, r.customer_tax_class_id, r.product_tax_class_id
FROM tax_calculation_rule r;
"

echo '=== tax classes ==='
mysql $DB -e "SELECT class_id, class_name, class_type FROM tax_class;"

echo '=== product tax class usage ==='
mysql $DB -e "
SELECT t.class_name, COUNT(*) c
FROM catalog_product_entity_int i
JOIN eav_attribute a ON i.attribute_id=a.attribute_id AND a.attribute_code='tax_class_id'
JOIN tax_class t ON t.class_id=i.value
WHERE i.store_id=0
GROUP BY t.class_name;
"
