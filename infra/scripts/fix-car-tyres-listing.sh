#!/bin/bash
set -euo pipefail
DB_HOST=tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com
DB_USER=magento
DB_PASS=uxaYMIQRwEU0AFl1Ck69LJnH
DB=tyresonline_sa

echo '=== BEFORE ==='
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB" -e \
  "SELECT website_id, COUNT(*) total, SUM(stock_status=1) in_stock FROM cataloginventory_stock_status GROUP BY website_id;"

echo '=== COPY stock status to website 1 and 2 ==='
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB" -e "
INSERT INTO cataloginventory_stock_status (product_id, website_id, stock_id, qty, stock_status)
SELECT product_id, 1, stock_id, qty, stock_status FROM cataloginventory_stock_status s
WHERE website_id = 0
ON DUPLICATE KEY UPDATE qty = VALUES(qty), stock_status = VALUES(stock_status);

INSERT INTO cataloginventory_stock_status (product_id, website_id, stock_id, qty, stock_status)
SELECT product_id, 2, stock_id, qty, stock_status FROM cataloginventory_stock_status s
WHERE website_id = 0
ON DUPLICATE KEY UPDATE qty = VALUES(qty), stock_status = VALUES(stock_status);
"

echo '=== FIX category url_path ==='
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB" -e "
UPDATE catalog_category_entity_varchar v
JOIN eav_attribute a ON v.attribute_id = a.attribute_id AND a.attribute_code = 'url_path' AND a.entity_type_id = 3
SET v.value = 'all-tyres'
WHERE v.entity_id = 1944 AND v.store_id = 0;

UPDATE catalog_category_entity_varchar v
JOIN eav_attribute a ON v.attribute_id = a.attribute_id AND a.attribute_code = 'url_path' AND a.entity_type_id = 3
SET v.value = 'all-tyres/car-tyres'
WHERE v.entity_id = 1945 AND v.store_id = 0;
"

cd /var/www/magento
echo '=== REINDEX ==='
sudo -u www-data php bin/magento indexer:reindex cataloginventory_stock catalog_product_flat catalog_category_product catalogsearch_fulltext 2>&1

echo '=== AFTER stock status ==='
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB" -e \
  "SELECT website_id, COUNT(*) total, SUM(stock_status=1) in_stock FROM cataloginventory_stock_status GROUP BY website_id;"

sudo -u www-data php bin/magento cache:enable block_html full_page 2>&1 || true
sudo -u www-data php bin/magento cache:flush 2>&1 | tail -3
sudo rm -rf var/page_cache/* 2>/dev/null || true

echo '=== PAGE TEST ==='
curl -s 'https://stg.tyresonline.sa/en/all-tyres/car-tyres.html?fix='$(date +%s) -o /tmp/car_fixed.html
echo "product-item-info: $(grep -c 'product-item-info' /tmp/car_fixed.html)"
grep -i "can't find" /tmp/car_fixed.html | head -1 || echo 'PRODUCTS LISTING OK'
