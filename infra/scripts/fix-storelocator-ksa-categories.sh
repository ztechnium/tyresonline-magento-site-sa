#!/bin/bash
set -euo pipefail
DB_HOST=tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com
DB_USER=magento
DB_PASS=uxaYMIQRwEU0AFl1Ck69LJnH
DB=tyresonline_sa

echo '=== Build KSA category ID list ==='
KSA_CATS=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB" -N -e \
  "SELECT GROUP_CONCAT(entity_id ORDER BY entity_id) FROM catalog_category_entity WHERE entity_id >= 1944")
echo "KSA categories: $KSA_CATS"

echo '=== Append KSA category IDs to active installer stores ==='
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB" -e "
UPDATE ecomteck_storelocator_stores
SET category = TRIM(BOTH ',' FROM CONCAT(IFNULL(category,''), ',', '$KSA_CATS'))
WHERE status = 1;
"

echo '=== Set map defaults to Riyadh, Saudi Arabia ==='
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB" -e "
INSERT INTO core_config_data (scope, scope_id, path, value) VALUES
('default', 0, 'ecomteck_storepickup/general/default_latitude', '24.7136'),
('default', 0, 'ecomteck_storepickup/general/default_longitude', '46.6753'),
('default', 0, 'ecomteck_storepickup/general/default_zoom', '10'),
('default', 0, 'ecomteck_storelocator/map/latitude', '24.7136'),
('default', 0, 'ecomteck_storelocator/map/longitude', '46.6753'),
('default', 0, 'ecomteck_storelocator/map/zoom', '10')
ON DUPLICATE KEY UPDATE value = VALUES(value);
"

cd /var/www/magento
sudo -u www-data php bin/magento cache:flush 2>&1 | tail -3

echo '=== Verify: stores with KSA category 1945 in list ==='
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB" -e \
  "SELECT COUNT(*) matched FROM ecomteck_storelocator_stores WHERE status=1 AND FIND_IN_SET('1945', category);"

echo '=== Note: AJAX needs active cart session; test on cart page with items ==='
